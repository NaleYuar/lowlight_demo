import sys
import os
import cv2
import numpy as np
import torch
from model import Network  

"""
1. 從命令列接 input_path / output_path
2. 建立並載入已訓練好的模型（best.pt）
3. 讀取一張影像，丟進模型做增亮
4. 把結果存成一張新圖片到指定 output_path
"""

def load_model(device):

    model = Network(stage=3).to(device)  

    base_dir = os.path.dirname(__file__) 
    ckpt_path = os.path.join(base_dir, "..", "weights", "best.pt")
    ckpt_path = os.path.abspath(ckpt_path)

    print("[INFO] load weights from:", ckpt_path) 

    ckpt = torch.load(ckpt_path, map_location=device)
    model.load_state_dict(ckpt)
    model.eval()
    return model


def enhance_one(model, device, in_path, out_path):

    if not os.path.exists(in_path):
        print(f"[ERR] input not found: {in_path}")
        sys.exit(1)

    img = cv2.imread(in_path, cv2.IMREAD_COLOR)
    if img is None:
        print(f"[ERR] cv2.imread failed: {in_path}")
        sys.exit(1)

    img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    img_f = img_rgb.astype(np.float32) / 255.0
    chw = np.transpose(img_f, (2, 0, 1))      # HWC -> CHW
    tensor = torch.from_numpy(chw).unsqueeze(0).to(device)  # 1xCxHxW

    with torch.no_grad():
        illu_list, ref_list, input_list, atten = model(tensor)
        pred = ref_list[-1]  

    pred = torch.clamp(pred, 0.0, 1.0)
    pred_np = pred.squeeze(0).cpu().numpy()   
    pred_np = np.transpose(pred_np, (1, 2, 0))  
    pred_np = (pred_np * 255.0).round().astype(np.uint8)

    pred_bgr = cv2.cvtColor(pred_np, cv2.COLOR_RGB2BGR)
    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    ok = cv2.imwrite(out_path, pred_bgr)
    if not ok:
        print(f"[ERR] cv2.imwrite failed: {out_path}")
        sys.exit(1)

    print("[OK]", in_path, "->", out_path)


def main():

    print("[DEBUG] argv:", sys.argv)

    if len(sys.argv) < 3:
        print("Usage: python enhance_cli.py input_path output_path")
        sys.exit(1)

    in_path = sys.argv[1]
    out_path = sys.argv[2]

    device = torch.device("cpu")
    model = load_model(device)
    enhance_one(model, device, in_path, out_path)


if __name__ == "__main__":
    main()
