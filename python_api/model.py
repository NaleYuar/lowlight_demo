"""IRE-SCI 低光影像增亮網路架構。

層級名稱與模組結構刻意維持和既有 ``best.pt`` 權重相容；整理程式碼時不可
改變 state_dict 使用的鍵值。
"""

from __future__ import annotations

from pathlib import Path

import torch
from torch import nn

from loss import LossFunction


class EnhanceNetwork(nn.Module):
    """估算目前 IRE-SCI 階段使用的照明圖。"""

    def __init__(self, layers: int, channels: int) -> None:
        """依指定的殘差層數與通道數建立照明估算網路。"""

        super().__init__()
        self.in_conv = nn.Sequential(
            nn.Conv2d(3, channels, kernel_size=3, stride=1, padding=1),
            nn.ReLU(),
        )
        self.conv = nn.Sequential(
            nn.Conv2d(channels, channels, kernel_size=3, stride=1, padding=1),
            nn.BatchNorm2d(channels),
            nn.ReLU(),
        )
        # 重複使用相同區塊，以完整對應既有訓練權重。
        self.blocks = nn.ModuleList([self.conv for _ in range(layers)])
        self.out_conv = nn.Sequential(
            nn.Conv2d(channels, 3, kernel_size=3, stride=1, padding=1),
            nn.Sigmoid(),
        )

    def forward(self, input_tensor: torch.Tensor) -> torch.Tensor:
        """從輸入影像估算照明圖，並限制數值避免後續除以零。"""

        features = self.in_conv(input_tensor)
        for convolution in self.blocks:
            features = features + convolution(features)
        illumination = self.out_conv(features) + input_tensor
        return torch.clamp(illumination, 0.0001, 1.0)


class CalibrateNetwork(nn.Module):
    """估算殘差，用來校準下一個 IRE-SCI 階段的輸入。"""

    def __init__(self, layers: int, channels: int) -> None:
        """依指定的殘差層數與通道數建立校準網路。"""

        super().__init__()
        self.layers = layers
        self.in_conv = nn.Sequential(
            nn.Conv2d(3, channels, kernel_size=3, stride=1, padding=1),
            nn.BatchNorm2d(channels),
            nn.ReLU(),
        )
        self.convs = nn.Sequential(
            nn.Conv2d(channels, channels, kernel_size=3, stride=1, padding=1),
            nn.BatchNorm2d(channels),
            nn.ReLU(),
            nn.Conv2d(channels, channels, kernel_size=3, stride=1, padding=1),
            nn.BatchNorm2d(channels),
            nn.ReLU(),
        )
        # 重複使用相同區塊，以完整對應既有訓練權重。
        self.blocks = nn.ModuleList([self.convs for _ in range(layers)])
        self.out_conv = nn.Sequential(
            nn.Conv2d(channels, 3, kernel_size=3, stride=1, padding=1),
            nn.Sigmoid(),
        )

    def forward(self, input_tensor: torch.Tensor) -> torch.Tensor:
        """從增亮結果估算用於下一階段的校準殘差。"""

        features = self.in_conv(input_tensor)
        for convolutions in self.blocks:
            features = features + convolutions(features)
        return input_tensor - self.out_conv(features)


class Network(nn.Module):
    """供模型訓練與網站推論使用的多階段 IRE-SCI 網路。"""

    def __init__(self, stage: int = 3) -> None:
        """建立指定階段數的增亮網路與校準網路。"""

        super().__init__()
        self.stage = stage
        self.enhance = EnhanceNetwork(layers=1, channels=3)
        self.calibrate = CalibrateNetwork(layers=3, channels=16)
        self._criterion = LossFunction()

    @staticmethod
    def weights_init(module: nn.Module) -> None:
        """初始化訓練所需的卷積層與批次正規化層權重。"""

        if isinstance(module, nn.Conv2d):
            module.weight.data.normal_(0, 0.02)
            if module.bias is not None:
                module.bias.data.zero_()
        elif isinstance(module, nn.BatchNorm2d):
            module.weight.data.normal_(1.0, 0.02)

    def forward(
        self, input_tensor: torch.Tensor
    ) -> tuple[list[torch.Tensor], list[torch.Tensor], list[torch.Tensor], list[torch.Tensor]]:
        """依序執行各階段，回傳照明圖、增亮圖、輸入與注意力殘差。"""

        illumination_stages: list[torch.Tensor] = []
        enhanced_stages: list[torch.Tensor] = []
        input_stages: list[torch.Tensor] = []
        attention_stages: list[torch.Tensor] = []
        stage_input = input_tensor

        for _ in range(self.stage):
            input_stages.append(stage_input)
            illumination = self.enhance(stage_input)
            enhanced = torch.clamp(input_tensor / illumination, 0.0, 1.0)
            attention = self.calibrate(enhanced)
            stage_input = input_tensor + attention

            illumination_stages.append(illumination)
            enhanced_stages.append(enhanced)
            attention_stages.append(torch.abs(attention))

        return illumination_stages, enhanced_stages, input_stages, attention_stages

    def _loss(self, input_tensor: torch.Tensor) -> torch.Tensor:
        """保留供舊權重工具使用的非監督式訓練損失介面。"""

        illumination_stages, _, input_stages, _ = self(input_tensor)
        losses = [
            self._criterion(input_stages[index], illumination_stages[index])
            for index in range(self.stage)
        ]
        return torch.stack(losses).sum()


class FineTuneModel(nn.Module):
    """保留供舊版訓練程式使用的單階段微調包裝器。"""

    def __init__(self, weights: str | Path) -> None:
        """建立單階段網路，並載入可與目前結構對應的權重。"""

        super().__init__()
        self.enhance = EnhanceNetwork(layers=1, channels=3)
        self._criterion = LossFunction()

        checkpoint = torch.load(weights, map_location="cpu")
        compatible = {
            key: value for key, value in checkpoint.items() if key in self.state_dict()
        }
        self.load_state_dict({**self.state_dict(), **compatible})

    weights_init = staticmethod(Network.weights_init)

    def forward(self, input_tensor: torch.Tensor) -> tuple[torch.Tensor, torch.Tensor]:
        """回傳輸入影像的照明圖與單階段增亮結果。"""

        illumination = self.enhance(input_tensor)
        enhanced = torch.clamp(input_tensor / illumination, 0.0, 1.0)
        return illumination, enhanced

    def _loss(self, input_tensor: torch.Tensor) -> torch.Tensor:
        """計算舊版微調流程使用的單階段損失。"""

        illumination, _ = self(input_tensor)
        return self._criterion(input_tensor, illumination)


# 舊版訓練程式使用的相容別名。
Finetunemodel = FineTuneModel
