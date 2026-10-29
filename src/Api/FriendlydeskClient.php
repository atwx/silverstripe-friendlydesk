<?php

namespace Atwx\Friendlydesk\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;

/**
 * Minimal client for the friendlydesk management API (/api/manage/v1).
 *
 * Configured by the environment variables FRIENDLYDESK_URL (host of the tenant,
 * e.g. https://ljr.friendlydesk.eu) and FRIENDLYDESK_TOKEN (API key of the tenant).
 */
class FriendlydeskClient
{
    use Configurable;
    use Injectable;

    /**
     * Request timeout in seconds
     */
    private static float $timeout = 5.0;

    private ?Client $client = null;

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->token() !== '';
    }

    /**
     * Forms that currently accept submissions, ordered by event date and title.
     *
     * @return array[] API records (id, title, slug, eventStart, publicUrl, ...)
     * @throws RuntimeException if the API cannot be reached
     */
    public function openForms(): array
    {
        $forms = array_values(array_filter(
            $this->get('forms', ['status' => 'open', 'limit' => 500])['items'] ?? [],
            fn (array $form) => !empty($form['open'])
        ));
        usort($forms, fn (array $a, array $b) => [$a['eventStart'] ?? '', $a['title']]
            <=> [$b['eventStart'] ?? '', $b['title']]);

        return $forms;
    }

    /**
     * @throws RuntimeException if the API cannot be reached or answers with an error
     */
    public function get(string $path, array $query = []): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('friendlydesk ist nicht konfiguriert (FRIENDLYDESK_URL, FRIENDLYDESK_TOKEN).');
        }

        try {
            $response = $this->client()->get('api/manage/v1/' . ltrim($path, '/'), ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('friendlydesk ist nicht erreichbar: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            throw new RuntimeException('friendlydesk hat keine gültige Antwort geliefert.');
        }

        return $data;
    }

    private function client(): Client
    {
        return $this->client ??= new Client([
            'base_uri' => $this->baseUrl() . '/',
            'timeout' => static::config()->get('timeout'),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept' => 'application/json',
            ],
        ]);
    }

    private function baseUrl(): string
    {
        return rtrim((string) Environment::getEnv('FRIENDLYDESK_URL'), '/');
    }

    private function token(): string
    {
        return (string) Environment::getEnv('FRIENDLYDESK_TOKEN');
    }
}
