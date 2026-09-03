"""IRE-SCI 模型訓練使用的損失函式。

網站推論不會計算這些損失值；保留本程式是為了讓 ``model.py``、既有權重與
訓練程式維持相容。
"""

from __future__ import annotations

import torch
from pytorch_msssim import ssim as structural_similarity
from torch import nn


YCBCR_MATRIX = (
    (0.257, -0.148, 0.439),
    (0.564, -0.291, -0.368),
    (0.098, 0.439, -0.071),
)
YCBCR_BIAS = (16 / 255.0, 128 / 255.0, 128 / 255.0)


class SmoothLoss(nn.Module):
    """利用低光輸入影像邊緣資訊計算平滑損失。"""

    def __init__(self, sigma: float = 0.1) -> None:
        """設定控制邊緣權重衰減程度的 sigma。"""

        super().__init__()
        self.sigma = sigma

    @staticmethod
    def rgb_to_ycbcr(tensor: torch.Tensor) -> torch.Tensor:
        """在原運算裝置上將 BCHW RGB Tensor 轉換為 YCbCr。"""

        batch, _, height, width = tensor.shape
        flat = tensor.permute(0, 2, 3, 1).reshape(-1, 3)
        matrix = tensor.new_tensor(YCBCR_MATRIX)
        bias = tensor.new_tensor(YCBCR_BIAS)
        converted = flat @ matrix + bias
        return converted.view(batch, height, width, 3).permute(0, 3, 1, 2)

    @staticmethod
    def _paired_slices(
        tensor: torch.Tensor, vertical: int, horizontal: int
    ) -> tuple[torch.Tensor, torch.Tensor]:
        """依像素位移取得相互重疊的來源區塊與相鄰區塊。"""

        height, width = tensor.shape[-2:]
        source_y = slice(max(vertical, 0), height + min(vertical, 0))
        target_y = slice(max(-vertical, 0), height - max(vertical, 0))
        source_x = slice(max(horizontal, 0), width + min(horizontal, 0))
        target_x = slice(max(-horizontal, 0), width - max(horizontal, 0))
        return (
            tensor[:, :, source_y, source_x],
            tensor[:, :, target_y, target_x],
        )

    def forward(self, low_light: torch.Tensor, prediction: torch.Tensor) -> torch.Tensor:
        """比較低光輸入與預測結果的相鄰像素，計算邊緣感知平滑損失。"""

        guide = self.rgb_to_ycbcr(low_light)
        scale = -1.0 / (2 * self.sigma * self.sigma)
        terms: list[torch.Tensor] = []
        height, width = guide.shape[-2:]

        for vertical in range(-2, 3):
            for horizontal in range(-2, 3):
                if (vertical == 0 and horizontal == 0) or abs(vertical) >= height or abs(horizontal) >= width:
                    continue

                guide_source, guide_neighbor = self._paired_slices(
                    guide, vertical, horizontal
                )
                output_source, output_neighbor = self._paired_slices(
                    prediction, vertical, horizontal
                )
                guide_difference = guide_source - guide_neighbor
                output_difference = output_source - output_neighbor
                edge_weight = torch.exp(
                    torch.sum(guide_difference.square(), dim=1, keepdim=True) * scale
                )
                terms.append(
                    torch.mean(
                        edge_weight
                        * torch.norm(output_difference, p=1, dim=1, keepdim=True)
                    )
                )

        return torch.stack(terms).mean() if terms else prediction.new_zeros(())


class SupervisedLoss(nn.Module):
    """監督式訓練使用的加權 L1、SSIM 與邊緣感知損失。"""

    def __init__(self, alpha: float = 1.0, beta: float = 1.0, mu: float = 0.1) -> None:
        """設定 L1、SSIM 與平滑損失的組合權重。"""

        super().__init__()
        self.alpha = alpha
        self.beta = beta
        self.mu = mu
        self.l1 = nn.L1Loss()
        self.smooth = SmoothLoss()

    def forward(
        self,
        low_light: torch.Tensor,
        prediction: torch.Tensor,
        ground_truth: torch.Tensor,
    ) -> tuple[torch.Tensor, dict[str, torch.Tensor]]:
        """計算總損失，並回傳各項損失供訓練紀錄使用。"""

        l1_loss = self.l1(prediction, ground_truth)
        ssim_value = structural_similarity(
            prediction,
            ground_truth,
            data_range=1.0,
            size_average=True,
            nonnegative_ssim=False,
        )
        ssim_loss = 1.0 - ssim_value
        smooth_loss = self.smooth(low_light.detach(), prediction)
        total = self.alpha * l1_loss + self.beta * ssim_loss + self.mu * smooth_loss
        return total, {
            "L1": l1_loss.detach(),
            "1-SSIM": ssim_loss.detach(),
            "Smooth": smooth_loss.detach(),
        }


class LossFunction(nn.Module):
    """供 ``Network._loss`` 使用並維持舊版相容性的非監督式損失。"""

    def __init__(self) -> None:
        """建立亮度保真損失與邊緣感知平滑損失。"""

        super().__init__()
        self.fidelity_loss = nn.MSELoss()
        self.smooth_loss = SmoothLoss()

    def forward(self, input_tensor: torch.Tensor, illumination: torch.Tensor) -> torch.Tensor:
        """計算輸入影像與照明圖之間的保真及平滑損失。"""

        fidelity = self.fidelity_loss(illumination, input_tensor)
        smoothness = self.smooth_loss(input_tensor, illumination)
        return 1.5 * fidelity + smoothness
