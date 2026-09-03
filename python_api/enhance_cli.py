"""單張低光影像增亮的命令列入口。

PHP 會傳入原圖與輸出圖的路徑。本程式載入單階段 IRE-SCI 模型、執行推論、
儲存增亮圖片，接著計算網頁顯示所需的影像資訊，最後以 JSON 回傳結果。
"""

from __future__ import annotations

import json
import os
import sys
from pathlib import Path
from typing import Any

import numpy as np
import torch
from PIL import Image

from image_analysis import compare_images, file_size_kb, load_rgb_image
from model import Network


MODEL_STAGE = 1
DEFAULT_CPU_THREADS = 2
WEIGHTS_PATH = Path(__file__).resolve().parent / "weights" / "best.pt"


def load_model(device: torch.device) -> Network:
    """將訓練完成的單階段 IRE-SCI 權重載入指定裝置。"""

    print(f"[INFO] Loading weights from {WEIGHTS_PATH}", file=sys.stderr)
    model = Network(stage=MODEL_STAGE).to(device)
    try:
        checkpoint = torch.load(WEIGHTS_PATH, map_location=device, weights_only=True)
    except TypeError:
        checkpoint = torch.load(WEIGHTS_PATH, map_location=device)
    model.load_state_dict(checkpoint)
    model.eval()
    return model


def image_to_tensor(image: Image.Image, device: torch.device) -> torch.Tensor:
    """將 RGB Pillow 圖片轉為數值介於 0～1 的 NCHW Tensor。"""

    array = np.asarray(image, dtype=np.float32) / 255.0
    channels_first = np.transpose(array, (2, 0, 1))
    return torch.from_numpy(channels_first).unsqueeze(0).to(device)


def predict(model: Network, input_tensor: torch.Tensor) -> torch.Tensor:
    """執行模型推論，並回傳與 test_supervised.py 相同的最後階段結果。"""

    with torch.inference_mode():
        _, enhanced_stages, _, _ = model(input_tensor)
    return torch.clamp(enhanced_stages[-1], 0.0, 1.0)


def tensor_to_image(tensor: torch.Tensor) -> Image.Image:
    """將數值介於 0～1 的 NCHW Tensor 轉回 RGB Pillow 圖片。"""

    array = tensor.squeeze(0).cpu().numpy()
    array = np.transpose(array, (1, 2, 0))
    pixels = (array * 255.0).round().astype(np.uint8)
    return Image.fromarray(pixels)


def save_image(image: Image.Image, output_path: Path) -> None:
    """依照副檔名使用合適的品質設定儲存增亮圖片。"""

    output_path.parent.mkdir(parents=True, exist_ok=True)
    if output_path.suffix.lower() in {".jpg", ".jpeg"}:
        image.save(output_path, quality=95, subsampling=0)
    else:
        image.save(output_path, compress_level=4)


def enhance_image(
    model: Network,
    device: torch.device,
    input_path: Path,
    output_path: Path,
) -> dict[str, Any]:
    """增亮單張圖片，並回傳 PHP 流程所需的影像分析資料。"""

    if not input_path.is_file():
        raise FileNotFoundError(f"Input image not found: {input_path}")

    original = load_rgb_image(input_path)
    enhanced = tensor_to_image(predict(model, image_to_tensor(original, device)))
    save_image(enhanced, output_path)

    analysis = compare_images(
        original,
        enhanced,
        original_size_kb=file_size_kb(input_path),
        enhanced_size_kb=file_size_kb(output_path),
    )
    return {"analysis": analysis}


def main(arguments: list[str] | None = None) -> int:
    """驗證命令列參數、執行推論，並將 JSON 結果輸出至標準輸出。"""

    arguments = sys.argv[1:] if arguments is None else arguments
    if len(arguments) != 2:
        print(json.dumps({"error": "usage"}), file=sys.stderr)
        return 2

    input_path, output_path = map(Path, arguments)
    thread_count = max(1, int(os.environ.get("TORCH_NUM_THREADS", DEFAULT_CPU_THREADS)))
    torch.set_num_threads(thread_count)
    device = torch.device("cpu")

    try:
        result = enhance_image(load_model(device), device, input_path, output_path)
    except (OSError, RuntimeError, ValueError) as exception:
        print(
            json.dumps({"error": "processing_failed", "detail": str(exception)}),
            file=sys.stderr,
        )
        return 1

    print(json.dumps({"ok": True, **result}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
