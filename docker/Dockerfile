FROM python:3.10-slim

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PIP_NO_CACHE_DIR=1 \
    TORCH_NUM_THREADS=2

WORKDIR /workspace


COPY requirements.txt ./requirements.txt

RUN pip install --no-cache-dir --upgrade pip \
 && pip install --no-cache-dir -r requirements.txt \
 && pip install --no-cache-dir --no-deps \
        --index-url https://download.pytorch.org/whl/cpu \
        torch==2.9.1+cpu \
 && pip install --no-cache-dir --no-deps pytorch-msssim==1.0.0

