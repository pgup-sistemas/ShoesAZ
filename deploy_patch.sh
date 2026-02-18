#!/usr/bin/env bash
# Script simple para criar um ZIP com arquivos alterados / manifest.
# Uso: ./deploy_patch.sh [remote_user remote_host remote_path]
set -euo pipefail
repo_root="$(cd "$(dirname "$0")" && pwd)"
cd "$repo_root"

manifest=(
  "app/Controllers/AuthController.php"
  "app/Views/dashboard/index.php"
  "public/index.php"
)

if command -v git >/dev/null 2>&1; then
  mapfile -t changed < <(git status --porcelain | awk '{print substr($0,4)}')
else
  changed=()
fi

if [ "${#changed[@]}" -eq 0 ]; then
  echo "Nenhuma alteração git detectada — usando manifest fixo..."
  files=("${manifest[@]}")
else
  files=("${changed[@]}")
fi

existing=()
for f in "${files[@]}"; do
  if [ -f "$f" ]; then
    existing+=("$f")
  else
    echo "Aviso: $f não encontrado, ignorando"
  fi
done

if [ "${#existing[@]}" -eq 0 ]; then
  echo "Nenhum arquivo para empacotar. Abortando." >&2
  exit 1
fi

deploy_dir="$repo_root/deploy"
mkdir -p "$deploy_dir"
ts=$(date +%Y%m%d_%H%M%S)
zipname="${deploy_dir}/patch_${ts}.zip"

zip -r "$zipname" "${existing[@]}"

echo "Pacote criado: $zipname"
echo "Arquivos incluídos:"
for f in "${existing[@]}"; do echo "  $f"; done

# envio opcional: ./deploy_patch.sh user host /remote/path
if [ "$#" -eq 3 ]; then
  user=$1; host=$2; rpath=$3
  scp "$zipname" "$user@$host:$rpath/"
fi
