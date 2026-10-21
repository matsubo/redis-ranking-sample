#!/usr/bin/env bash
# Manual deploy of the committed HEAD to the home k3s cluster (namespace ranking).
#
#   deploy/deploy.sh
#
# 1. builds ghcr.io/matsubo/redis-ranking-sample:<sha> for linux/amd64 and pushes
#    it to GHCR (needs `docker login ghcr.io` with write:packages),
# 2. imports the same image into containerd on every node, so the rollout does
#    not depend on a registry pull,
# 3. applies deploy/k8s.yaml through kubectl on $KUBE_HOST and waits for it.
#
# Needs ssh as matsu to the nodes (tailnet) and their sudo password, read from
# SUDO_PASSWORD or prompted.
set -euo pipefail
cd "$(dirname "$0")/.."

REPO=ghcr.io/matsubo/redis-ranking-sample
read -ra NODES <<<"${NODES:-t1 t2 t3 gmk}"
KUBE_HOST=${KUBE_HOST:-t1}

if [ -n "$(git status --porcelain)" ]; then
  echo "working tree has uncommitted changes; the image tag is the commit sha" >&2
  exit 1
fi
IMAGE=$REPO:$(git rev-parse HEAD)

if [ -z "${SUDO_PASSWORD:-}" ]; then
  read -rsp "sudo password for ${NODES[*]}: " SUDO_PASSWORD
  echo
fi

echo "==> build $IMAGE"
docker buildx build --platform linux/amd64 --load -t "$IMAGE" -t "$REPO:master" .

echo "==> push"
docker push "$IMAGE"
docker push "$REPO:master"

tar=$(mktemp -t ranking-image)
trap 'rm -f "$tar"' EXIT
docker save "$IMAGE" -o "$tar"

for node in "${NODES[@]}"; do
  echo "==> import on $node"
  scp -q "$tar" "matsu@$node:/tmp/ranking-image.tar"
  ssh "matsu@$node" 'sudo -S -p "" k3s ctr -n k8s.io images import /tmp/ranking-image.tar >/dev/null && rm -f /tmp/ranking-image.tar' <<<"$SUDO_PASSWORD"
done

echo "==> roll out"
sed "s|image: IMAGE$|image: $IMAGE|" deploy/k8s.yaml |
  ssh "matsu@$KUBE_HOST" 'export KUBECONFIG=$HOME/.kube/config; kubectl apply -f - && kubectl -n ranking rollout status deploy/ranking --timeout=300s'
