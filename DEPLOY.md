Deploy rápido — ShoesAZ

Arquivos de utilidade para empacotar e enviar as alterações ao servidor:

- `deploy_patch.ps1` — script PowerShell (Windows). Cria `deploy/patch_YYYYMMDD_HHMMSS.zip` com os arquivos modificados (usa `git` quando disponível).
- `deploy_patch.sh` — script shell (Linux/macOS). Mesma finalidade.

Como usar (Windows):

1. Abra PowerShell na raiz do projeto.
2. Rode: `.	ransform_patch.ps1` *(ou)* `.\
deploy_patch.ps1` (ex.: `.\
deploy_patch.ps1 -UploadHost 1.2.3.4 -UploadUser deploy -UploadPath '/var/www/ShoesAZ'`).
3. Faça upload do ZIP gerado (`deploy/patch_YYYYMMDD_HHMMSS.zip`) e extraia no servidor.

Como usar (Linux/macOS):

1. Rode `./deploy_patch.sh`.
2. (Opcional) `./deploy_patch.sh deploy 1.2.3.4 /var/www/ShoesAZ` — envia via `scp`.

Observações:
- Os scripts não sobrescrevem arquivos do servidor automaticamente (a menos que você use `scp` e extraia manualmente no servidor).
- Recomenda-se sempre backup/versão no servidor antes de substituir arquivos.
- Arquivos incluídos por padrão nesta atualização: `app/Controllers/AuthController.php`, `app/Views/dashboard/index.php`, `public/index.php`.

Se quiser, eu crio um script que faça upload + backup remoto automático (SCP + backup tar) — deseja isso?