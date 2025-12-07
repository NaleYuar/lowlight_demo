import torch
import torch.nn as nn
import torch.nn.functional as F
from pytorch_msssim import ssim as ssim_lib

class SmoothLoss(nn.Module):
    def __init__(self):
        super().__init__()
        self.sigma = 10

    def rgb2yCbCr(self, x):
        B, C, H, W = x.shape
        flat = x.permute(0,2,3,1).reshape(-1, 3).to(dtype=x.dtype)
        mat  = torch.tensor([[0.257, -0.148, 0.439],
                            [0.564, -0.291, -0.368],
                            [0.098,  0.439, -0.071]], device=x.device, dtype=x.dtype)
        bias = torch.tensor([16/255., 128/255., 128/255.], device=x.device, dtype=x.dtype)
        temp = flat @ mat + bias
        return temp.view(B, H, W, 3).permute(0,3,1,2)

    # input = I_gt, output = I_pred
    def forward(self, input, output):
        y = output
        x = self.rgb2yCbCr(input)
        s = -1.0 / (2 * self.sigma * self.sigma)

        def w(diff): return torch.exp(torch.sum(diff*diff, dim=1, keepdim=True) * s)
        p = 1.0

        terms = []
        diffs = [
            (x[:, :, 1:, :] - x[:, :, :-1, :],  y[:, :, 1:, :] - y[:, :, :-1, :]),
            (x[:, :, :-1, :] - x[:, :, 1:, :],  y[:, :, :-1, :] - y[:, :, 1:, :]),
            (x[:, :, :, 1:] - x[:, :, :, :-1],  y[:, :, :, 1:] - y[:, :, :, :-1]),
            (x[:, :, :, :-1] - x[:, :, :, 1:],  y[:, :, :, :-1] - y[:, :, :, 1:]),
            (x[:, :, :-1, :-1] - x[:, :, 1:, 1:], y[:, :, :-1, :-1] - y[:, :, 1:, 1:]),
            (x[:, :, 1:, 1:] - x[:, :, :-1, :-1], y[:, :, 1:, 1:] - y[:, :, :-1, :-1]),
            (x[:, :, 1:, :-1] - x[:, :, :-1, 1:], y[:, :, 1:, :-1] - y[:, :, :-1, 1:]),
            (x[:, :, :-1, 1:] - x[:, :, 1:, :-1], y[:, :, :-1, 1:] - y[:, :, 1:, :-1]),
            (x[:, :, 2:, :] - x[:, :, :-2, :],   y[:, :, 2:, :] - y[:, :, :-2, :]),
            (x[:, :, :-2, :] - x[:, :, 2:, :],   y[:, :, :-2, :] - y[:, :, 2:, :]),
            (x[:, :, :, 2:] - x[:, :, :, :-2],   y[:, :, :, 2:] - y[:, :, :, :-2]),
            (x[:, :, :, :-2] - x[:, :, :, 2:],   y[:, :, :, :-2] - y[:, :, :, 2:]),
            (x[:, :, :-2, :-1] - x[:, :, 2:, 1:], y[:, :, :-2, :-1] - y[:, :, 2:, 1:]),
            (x[:, :, 2:, 1:] - x[:, :, :-2, :-1], y[:, :, 2:, 1:] - y[:, :, :-2, :-1]),
            (x[:, :, 2:, :-1] - x[:, :, :-2, 1:], y[:, :, 2:, :-1] - y[:, :, :-2, 1:]),
            (x[:, :, :-2, 1:] - x[:, :, 2:, :-1], y[:, :, :-2, 1:] - y[:, :, 2:, :-1]),
            (x[:, :, :-1, :-2] - x[:, :, 1:, 2:], y[:, :, :-1, :-2] - y[:, :, 1:, 2:]),
            (x[:, :, 1:, 2:] - x[:, :, :-1, :-2], y[:, :, 1:, 2:] - y[:, :, :-1, :-2]),
            (x[:, :, 1:, :-2] - x[:, :, :-1, 2:], y[:, :, 1:, :-2] - y[:, :, :-1, 2:]),
            (x[:, :, :-1, 2:] - x[:, :, 1:, :-2], y[:, :, :-1, 2:] - y[:, :, 1:, :-2]),
            (x[:, :, :-2, :-2] - x[:, :, 2:, 2:], y[:, :, :-2, :-2] - y[:, :, 2:, 2:]),
            (x[:, :, 2:, 2:] - x[:, :, :-2, :-2], y[:, :, 2:, 2:] - y[:, :, :-2, :-2]),
            (x[:, :, 2:, :-2] - x[:, :, :-2, 2:], y[:, :, 2:, :-2] - y[:, :, :-2, 2:]),
            (x[:, :, :-2, 2:] - x[:, :, 2:, :-2], y[:, :, :-2, 2:] - y[:, :, 2:, :-2]),
        ]
        num = 0
        for dx, dy in diffs:
            term = torch.mean(w(dx) * torch.norm(dy, p=1, dim=1, keepdim=True))
            terms.append(term); num += 1
        return sum(terms) / max(num, 1)

class SupervisedLoss(nn.Module):
    def __init__(self, alpha=1.0, beta=1.0, mu=0.1):
        super().__init__()
        self.alpha, self.beta, self.mu = alpha, beta, mu
        self.l1 = nn.L1Loss()
        self.smooth = SmoothLoss() 

    def forward(self, I_low, I_pred, I_gt):
        l_l1 = self.l1(I_pred, I_gt)
        ssim_val = ssim_lib(I_pred, I_gt, data_range=1.0,size_average=True, nonnegative_ssim=False)
        l_ssim = 1.0 - ssim_val
        l_s = self.smooth(I_low.detach(), I_pred)
        total = self.alpha*l_l1 + self.beta*l_ssim + self.mu*l_s
        return total, {"L1": l_l1.detach(), "1-SSIM": l_ssim.detach(), "Smooth": l_s.detach()}
    
class LossFunction(nn.Module):
    def __init__(self):
        super(LossFunction, self).__init__()
        self.l2_loss = nn.MSELoss()
        self.smooth_loss = SmoothLoss()

    def forward(self, input, illu):
        Fidelity_Loss = self.l2_loss(illu, input)
        Smooth_Loss = self.smooth_loss(input, illu)
        return 1.5*Fidelity_Loss + Smooth_Loss
