import sys, os, json, math
import cv2
import numpy as np
import torch
from pytorch_msssim import ssim as ssim_lib

def to_tensor(img_bgr):
    # HWC BGR [0,255] → CHW RGB [0,1]
    img_rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)
    img = img_rgb.astype(np.float32) / 255.0
    img = np.transpose(img, (2, 0, 1))  # CHW
    t = torch.from_numpy(img).unsqueeze(0)  # 1xCxHxW
    return t

def psnr(pred, gt):
    mse = torch.mean((pred - gt) ** 2).item()
    if mse == 0:
        return 99.0
    return 10.0 * math.log10(1.0 / mse)

def l1_loss(pred, gt):
    return torch.mean(torch.abs(pred - gt)).item()

def main():
    if len(sys.argv) != 3:
        print(json.dumps({"error": "usage: metrics_cli.py orig_path enh_path"}))
        return

    orig_path = sys.argv[1]
    enh_path  = sys.argv[2]

    if (not os.path.exists(orig_path)) or (not os.path.exists(enh_path)):
        print(json.dumps({"error": "file not found"}))
        return

    orig = cv2.imread(orig_path, cv2.IMREAD_COLOR)
    enh  = cv2.imread(enh_path,  cv2.IMREAD_COLOR)

    if orig is None or enh is None:
        print(json.dumps({"error": "imread failed"}))
        return

    # 尺寸對齊
    if orig.shape != enh.shape:
        enh = cv2.resize(enh, (orig.shape[1], orig.shape[0]), interpolation=cv2.INTER_CUBIC)

    t_orig = to_tensor(orig)
    t_enh  = to_tensor(enh)

    with torch.no_grad():
        psnr_val = psnr(t_enh, t_orig)
        l1_val   = l1_loss(t_enh, t_orig)
        ssim_val = ssim_lib(t_enh, t_orig, data_range=1.0, size_average=True).item()

    out = {
        "psnr": round(psnr_val, 4),
        "ssim": round(ssim_val, 4),
        "l1":   round(l1_val,   6)
    }
    print(json.dumps(out))

if __name__ == "__main__":
    main()
