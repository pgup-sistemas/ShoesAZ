<?php

declare(strict_types=1);

final class HttpSmoke
{
    private string $baseUrl;
    private array $errors = [];
    private array $warnings = [];
    private array $cookies = [];

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function run(): int
    {
        if (!function_exists('curl_init')) {
            $this->errors[] = 'Extensão PHP "curl" não está habilitada. Ative para rodar testes HTTP.';
            $this->printReport();
            return 1;
        }

        $this->checkPublicRoutes();
        $this->maybeLoginAndCheckPrivateRoutes();
        $this->printReport();

        return $this->errors ? 1 : 0;
    }

    private function checkPublicRoutes(): void
    {
        $this->expectGet('/login', 200);
        $this->expectGet('/recuperar-senha', 200);
    }

    private function maybeLoginAndCheckPrivateRoutes(): void
    {
        $login = (string) (getenv('SHOESAZ_TEST_LOGIN') ?: '');
        $pass = (string) (getenv('SHOESAZ_TEST_PASSWORD') ?: '');

        if ($login === '' || $pass === '') {
            $this->warnings[] = 'SHOESAZ_TEST_LOGIN/SHOESAZ_TEST_PASSWORD não definidos; pulando testes autenticados.';
            $this->expectGet('/', 302, true);
            $this->expectGet('/os', 302, true);
            $this->expectGet('/recibos', 302, true);
            $this->expectGet('/backup', 302, true);
            return;
        }

        $ok = $this->login($login, $pass);
        if (!$ok) {
            $this->errors[] = 'Falha no login de teste; verifique credenciais nas variáveis de ambiente.';
            return;
        }

        $this->expectGet('/', 200);
        $this->expectGet('/os', 200);
        $this->expectGet('/clientes', 200);
        $this->expectGet('/orcamentos', 200);
        $this->expectGet('/pagamentos', 200);
        $this->expectGet('/contas-receber', 200);
        $this->expectGet('/recibos', 200);
        $this->expectGet('/backup', 200);
        $this->expectGet('/configuracoes/empresa', 200);
    }

    private function login(string $login, string $password): bool
    {
        $resp = $this->request('GET', '/login');
        if ($resp === null) {
            return false;
        }

        $csrf = $this->extractCsrfToken($resp['body']);
        if ($csrf === null) {
            $this->warnings[] = 'Não foi possível extrair CSRF do /login; tentando login sem CSRF.';
        }

        $post = [
            'login' => $login,
            'senha' => $password,
        ];
        if ($csrf !== null) {
            $post['_csrf'] = $csrf;
        }

        $resp = $this->request('POST', '/login', $post, false);
        if ($resp === null) {
            return false;
        }

        if ($resp['status'] === 302 || $resp['status'] === 303) {
            return true;
        }

        if ($resp['status'] === 200 && stripos($resp['body'], 'Senha') === false) {
            return true;
        }

        return false;
    }

    private function expectGet(string $path, int $expectedStatus, bool $allowRedirect = false): void
    {
        $resp = $this->request('GET', $path, null, $allowRedirect);
        if ($resp === null) {
            $this->errors[] = "GET {$path}: sem resposta";
            return;
        }

        if ($allowRedirect && ($resp['status'] === 301 || $resp['status'] === 302 || $resp['status'] === 303)) {
            return;
        }

        // Alguns ambientes retornam 200 já com a tela de login (em vez de 302) quando não autenticado.
        if ($allowRedirect && $expectedStatus === 302 && $resp['status'] === 200 && $this->isLoginPage($resp['body'])) {
            return;
        }

        if ($resp['status'] !== $expectedStatus) {
            $this->errors[] = "GET {$path}: esperado {$expectedStatus}, recebeu {$resp['status']}";
        }
    }

    private function isLoginPage(string $html): bool
    {
        return (bool) preg_match('/name="login"/i', $html) && (bool) preg_match('/name="senha"/i', $html);
    }

    private function request(string $method, string $path, ?array $postFields = null, bool $followRedirects = false): ?array
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        $headers = [];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($this->cookies) {
            $cookieHeader = [];
            foreach ($this->cookies as $k => $v) {
                $cookieHeader[] = $k . '=' . $v;
            }
            $headers[] = 'Cookie: ' . implode('; ', $cookieHeader);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postFields !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            }
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $this->errors[] = "{$method} {$path}: " . curl_error($ch);
            curl_close($ch);
            return null;
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        $this->captureCookies($rawHeaders);

        return [
            'status' => $status,
            'headers' => $rawHeaders,
            'body' => $body,
        ];
    }

    private function captureCookies(string $rawHeaders): void
    {
        foreach (preg_split("/\r\n/", $rawHeaders) as $line) {
            if (stripos($line, 'Set-Cookie:') === 0) {
                $cookieStr = trim(substr($line, strlen('Set-Cookie:')));
                $parts = explode(';', $cookieStr);
                $kv = explode('=', trim($parts[0]), 2);
                if (count($kv) === 2) {
                    $this->cookies[$kv[0]] = $kv[1];
                }
            }
        }
    }

    private function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/name="_csrf"\s+value="([^"]+)"/i', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    private function printReport(): void
    {
        echo "\n=== ShoesAZ HTTP Smoke ===\n";
        echo 'Base URL: ' . $this->baseUrl . "\n";
        echo 'Data: ' . date('Y-m-d H:i:s') . "\n\n";

        if (!$this->errors && !$this->warnings) {
            echo "OK: Rotas principais responderam conforme esperado.\n";
            return;
        }

        if ($this->errors) {
            echo "ERROS:\n";
            foreach ($this->errors as $e) {
                echo '- ' . $e . "\n";
            }
            echo "\n";
        }

        if ($this->warnings) {
            echo "AVISOS:\n";
            foreach ($this->warnings as $w) {
                echo '- ' . $w . "\n";
            }
            echo "\n";
        }

        echo $this->errors ? "RESULTADO: FAIL\n" : "RESULTADO: OK (com avisos)\n";
    }
}

$baseUrl = $argv[1] ?? (getenv('SHOESAZ_BASE_URL') ?: '');
if (!is_string($baseUrl) || trim($baseUrl) === '') {
    fwrite(STDERR, "Uso:\n");
    fwrite(STDERR, "  php tests/http_smoke.php https://localhost/ShoesAZ\n\n");
    fwrite(STDERR, "Ou defina variáveis de ambiente:\n");
    fwrite(STDERR, "  SHOESAZ_BASE_URL=https://localhost/ShoesAZ\n");
    fwrite(STDERR, "  SHOESAZ_TEST_LOGIN=admin\n");
    fwrite(STDERR, "  SHOESAZ_TEST_PASSWORD=senha\n");
    exit(2);
}

$runner = new HttpSmoke((string) $baseUrl);
exit($runner->run());
