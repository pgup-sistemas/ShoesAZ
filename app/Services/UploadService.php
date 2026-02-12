<?php

declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    private static string $basePath = __DIR__ . '/../../public/uploads/sapatos/';
    private static int $maxFileSize = 5 * 1024 * 1024; // 5MB
    private static array $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    public static function uploadSapatoFoto(array $file, int $sapatoId): ?string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        if ($file['size'] > self::$maxFileSize) {
            throw new \RuntimeException('Arquivo muito grande. Máximo 5MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::$allowedTypes, true)) {
            throw new \RuntimeException('Tipo de arquivo não permitido. Use JPG, PNG ou WebP.');
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $fileName = sprintf('%d_%s.%s', $sapatoId, bin2hex(random_bytes(8)), $ext);
        $subDir = date('Y/m');
        $fullDir = self::$basePath . $subDir;

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $fullPath = $fullDir . '/' . $fileName;
        $webPath = '/public/uploads/sapatos/' . $subDir . '/' . $fileName;

        // Redimensionar e comprimir imagem
        self::processarImagem($file['tmp_name'], $fullPath, $mimeType);

        return $webPath;
    }

    private static function processarImagem(string $source, string $dest, string $mimeType): void
    {
        $maxWidth = 1200;
        $maxHeight = 1200;
        $quality = 85;

        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $srcImage = imagecreatefrompng($source);
                break;
            case 'image/webp':
                $srcImage = imagecreatefromwebp($source);
                break;
            default:
                throw new \RuntimeException('Formato de imagem não suportado.');
        }

        if (!$srcImage) {
            throw new \RuntimeException('Erro ao processar imagem.');
        }

        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);

        // Calcular novas dimensões mantendo proporção
        if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
            $newWidth = (int) ($origWidth * $ratio);
            $newHeight = (int) ($origHeight * $ratio);
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparência para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Salvar imagem processada
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($dstImage, $dest, $quality);
                break;
            case 'image/png':
                imagepng($dstImage, $dest, (int) round(9 * (100 - $quality) / 100));
                break;
            case 'image/webp':
                imagewebp($dstImage, $dest, $quality);
                break;
        }

        imagedestroy($srcImage);
        imagedestroy($dstImage);
    }

    public static function removerFoto(string $path): bool
    {
        $normalized = $path;
        if (str_starts_with($normalized, '/uploads/')) {
            $normalized = '/public' . $normalized;
        }

        $fullPath = __DIR__ . '/../../' . ltrim($normalized, '/');
        if (is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}
