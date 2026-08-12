<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid {--force : Overwrite existing VAPID keys in .env}';

    protected $description = 'Generate VAPID keys and write them to .env (private key is not printed)';

    public function handle(): int
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            $this->error('.env file not found.');

            return self::FAILURE;
        }

        $env = File::get($envPath);
        $hasKeys = preg_match('/^VAPID_PUBLIC_KEY=.+/m', $env) && preg_match('/^VAPID_PRIVATE_KEY=.+/m', $env);

        if ($hasKeys && ! $this->option('force')) {
            $this->info('VAPID keys already exist in .env. Use --force to regenerate.');

            return self::SUCCESS;
        }

        $keys = $this->generateWithNode() ?? $this->generateWithPhpOpenSsl();
        if ($keys === null) {
            $this->error('Failed to generate VAPID keys. Need Node.js or PHP OpenSSL EC support.');

            return self::FAILURE;
        }

        $env = $this->upsertEnv($env, 'VAPID_SUBJECT', 'mailto:admin@autoluz.local');
        $env = $this->upsertEnv($env, 'VAPID_PUBLIC_KEY', $keys['publicKey']);
        $env = $this->upsertEnv($env, 'VAPID_PRIVATE_KEY', $keys['privateKey']);
        File::put($envPath, $env);

        $this->info('VAPID keys written to .env.');
        $this->line('Public key length: '.strlen($keys['publicKey']));

        return self::SUCCESS;
    }

    /**
     * @return array{publicKey:string,privateKey:string}|null
     */
    private function generateWithNode(): ?array
    {
        $script = <<<'JS'
const { generateKeyPairSync } = require('crypto');
const { privateKey, publicKey } = generateKeyPairSync('ec', { namedCurve: 'P-256' });
const priv = privateKey.export({ format: 'jwk' });
const pub = publicKey.export({ format: 'jwk' });
function b64urlToBuf(s) { return Buffer.from(s, 'base64url'); }
const publicKeyRaw = Buffer.concat([Buffer.from([0x04]), b64urlToBuf(pub.x), b64urlToBuf(pub.y)]);
process.stdout.write(JSON.stringify({
  publicKey: publicKeyRaw.toString('base64url'),
  privateKey: priv.d,
}));
JS;

        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'autoluz_vapid_'.bin2hex(random_bytes(4)).'.js';
        File::put($tmp, $script);

        try {
            $output = shell_exec('node '.escapeshellarg($tmp).' 2>&1');
            $decoded = json_decode(trim((string) $output), true);
            if (! is_array($decoded) || empty($decoded['publicKey']) || empty($decoded['privateKey'])) {
                return null;
            }

            return [
                'publicKey' => (string) $decoded['publicKey'],
                'privateKey' => (string) $decoded['privateKey'],
            ];
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * @return array{publicKey:string,privateKey:string}|null
     */
    private function generateWithPhpOpenSsl(): ?array
    {
        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($key === false) {
            return null;
        }

        $details = openssl_pkey_get_details($key);
        if (! is_array($details) || empty($details['ec']['x']) || empty($details['ec']['y']) || empty($details['ec']['d'])) {
            return null;
        }

        $publicRaw = "\x04".$this->padEcCoord($details['ec']['x']).$this->padEcCoord($details['ec']['y']);

        return [
            'publicKey' => $this->base64UrlEncode($publicRaw),
            'privateKey' => $this->base64UrlEncode($this->padEcCoord($details['ec']['d'])),
        ];
    }

    private function padEcCoord(string $bin): string
    {
        if (strlen($bin) < 32) {
            return str_pad($bin, 32, "\0", STR_PAD_LEFT);
        }

        if (strlen($bin) > 32) {
            return substr($bin, -32);
        }

        return $bin;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function upsertEnv(string $env, string $key, string $value): string
    {
        $line = $key.'='.$value;
        if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $env)) {
            return preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $env) ?? $env;
        }

        return rtrim($env)."\n".$line."\n";
    }
}
