"""計算 Web 工具使用的無參考影像資訊。

本程式只比較使用者上傳的原圖與模型增亮結果，不需要 Ground Truth（GT）。
回傳欄位名稱與資料庫欄位一致，並在欄位名稱中標示單位。
"""

from __future__ import annotations

import json
import sys
from pathlib import Path
from typing import TypedDict

import numpy as np
from PIL import Image, ImageOps


LUMA_WEIGHTS = np.array([0.2126, 0.7152, 0.0722], dtype=np.float32)
PERCENTILE_LOW = 5
PERCENTILE_HIGH = 95
BYTES_PER_KILOBYTE = 1024


class ImageStatistics(TypedDict):
    """單張 RGB 圖片的亮度與對比統計。"""

    brightness_pct: float
    contrast_pct: float


class ImageComparison(TypedDict):
    """一組原圖與增亮圖要寫入資料庫的比較資料。"""

    brightness_before_pct: float
    brightness_after_pct: float
    contrast_before_pct: float
    contrast_after_pct: float
    image_width_px: int
    image_height_px: int
    original_size_kb: float
    enhanced_size_kb: float


def calculate_image_statistics(image: Image.Image) -> ImageStatistics:
    """以百分比回傳平均亮度與排除極端值後的對比範圍。"""

    rgb = np.asarray(image.convert("RGB"), dtype=np.float32) / 255.0
    luminance = np.clip(rgb, 0.0, 1.0) @ LUMA_WEIGHTS
    low, high = np.percentile(luminance, [PERCENTILE_LOW, PERCENTILE_HIGH])

    return {
        "brightness_pct": float(np.mean(luminance) * 100.0),
        "contrast_pct": float((high - low) * 100.0),
    }


def compare_images(
    original: Image.Image,
    enhanced: Image.Image,
    original_size_kb: float,
    enhanced_size_kb: float,
) -> ImageComparison:
    """整理兩張相同尺寸圖片的亮度、對比、尺寸與檔案大小。"""

    before = calculate_image_statistics(original)
    after = calculate_image_statistics(enhanced)

    return {
        "brightness_before_pct": round(before["brightness_pct"], 3),
        "brightness_after_pct": round(after["brightness_pct"], 3),
        "contrast_before_pct": round(before["contrast_pct"], 3),
        "contrast_after_pct": round(after["contrast_pct"], 3),
        "image_width_px": original.width,
        "image_height_px": original.height,
        "original_size_kb": round(original_size_kb, 2),
        "enhanced_size_kb": round(enhanced_size_kb, 2),
    }


def file_size_kb(path: Path) -> float:
    """以二進位 KB 回傳檔案大小（1 KB = 1024 bytes）。"""

    return path.stat().st_size / BYTES_PER_KILOBYTE


def load_rgb_image(path: Path) -> Image.Image:
    """讀取圖片、套用 EXIF 方向資訊，並回傳獨立的 RGB 圖片。"""

    with Image.open(path) as source:
        return ImageOps.exif_transpose(source).convert("RGB")


def analyze_file_pair(original_path: Path, enhanced_path: Path) -> ImageComparison:
    """讀取原圖與增亮圖，並產生可直接寫入資料庫的比較資料。"""

    original = load_rgb_image(original_path)
    enhanced = load_rgb_image(enhanced_path)
    if enhanced.size != original.size:
        enhanced = enhanced.resize(original.size, Image.Resampling.BICUBIC)

    return compare_images(
        original,
        enhanced,
        original_size_kb=file_size_kb(original_path),
        enhanced_size_kb=file_size_kb(enhanced_path),
    )


def main(arguments: list[str] | None = None) -> int:
    """提供除錯與回歸測試使用的命令列入口。"""

    arguments = sys.argv[1:] if arguments is None else arguments
    if len(arguments) != 2:
        print(
            json.dumps({"error": "usage: image_analysis.py original_path enhanced_path"}),
            file=sys.stderr,
        )
        return 2

    original_path, enhanced_path = map(Path, arguments)
    if not original_path.is_file() or not enhanced_path.is_file():
        print(json.dumps({"error": "file_not_found"}), file=sys.stderr)
        return 1

    try:
        analysis = analyze_file_pair(original_path, enhanced_path)
    except (OSError, ValueError) as exception:
        print(
            json.dumps({"error": "analysis_failed", "detail": str(exception)}),
            file=sys.stderr,
        )
        return 1

    print(json.dumps({"analysis": analysis}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
