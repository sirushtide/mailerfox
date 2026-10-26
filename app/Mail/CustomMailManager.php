<?php

namespace App\Mail;

use App\Mail\Transport\GmailApiTransport;
use App\Mail\Transport\SendGridTransport;
use App\Mail\Transport\SesReplyToTransport;
use App\Mail\Transport\SparkPostTransport;
use App\Mail\Transport\ResendTransport;
use Aws\Ses\SesClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Arr;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class CustomMailManager extends MailManager
{
    /**
     * Create an instance of the Symfony Amazon SES ReplyTo Transport driver.
     *
     * @param  array  $config
     * @return \App\Mail\Transport\SesReplyToTransport
     */
    protected function createSesReplyToTransport(array $config)
    {
        $config = array_merge(
            $this->app['config']->get('services.ses', []),
            $config
        );

        $config = Arr::except($config, ['transport']);

        return new SesReplyToTransport(
            new SesClient($this->addSesCredentials($config)),
            $config['options'] ?? []
        );
    }

    /**
     * Add AWS SES credentials to the configuration array.
     *
     * @param  array  $config
     * @return array
     */
    protected function addSesCredentials(array $config): array
    {
        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        unset($config['key'], $config['secret'], $config['token']);

        return $config;
    }

    /**
     * Create an instance of the Symfony SparkPost Transport driver.
     *
     * @param  array  $config
     * @return \App\Mail\Transport\SparkPostTransport
     */
    protected function createSparkpostTransport(array $config)
    {
        $config = array_merge(
            $this->app['config']->get('services.sparkpost', []),
            $config
        );

        $config = Arr::except($config, ['transport']);

        if (empty($config['secret'])) {
            throw new \InvalidArgumentException('SparkPost API key is required.');
        }

        return new SparkPostTransport(
            $config['secret'],
            $config['endpoint'] ?? 'https://api.sparkpost.com',
            $config['options'] ?? []
        );
    }

    /**
     * Create an instance of the SendGrid Transport driver.
     *
     * @param  array  $config
     * @return \App\Mail\Transport\SendGridTransport
     */
    protected function createSendgridTransport(array $config)
    {
        $config = array_merge(
            $this->app['config']->get('services.sendgrid', []),
            $config
        );

        $config = Arr::except($config, ['transport']);

        if (empty($config['api_key'])) {
            throw new \InvalidArgumentException('SendGrid API key is required.');
        }

        return new SendGridTransport($config['api_key']);
    }

    /**
     * Create an instance of the Symfony Mailgun Transport driver.
     *
     * @param  array  $config
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    protected function createGmailApiTransport(array $config): GmailApiTransport
    {
        return new GmailApiTransport(
            $config['client_id'] ?? '',
            $config['client_secret'] ?? '',
            $config['refresh_token'] ?? ''
        );
    }

    /**
     * Create an instance of the Resend Transport driver.
     *
     * @param  array  $config
     * @return \App\Mail\Transport\ResendTransport
     */
    protected function createResendTransport(array $config)
    {
        $config = array_merge(
            $this->app['config']->get('services.resend', []),
            $config
        );

        $config = Arr::except($config, ['transport']);

        $apiKey = $config['api_key'] ?? $config['key'] ?? $config['secret'] ?? null;

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Resend API key is required.');
        }

        return new ResendTransport(
            $apiKey,
            $config['endpoint'] ?? 'https://api.resend.com',
            $config['options'] ?? []
        );
    }

    protected function createMailgunTransport(array $config)
    {
        $factory = new MailgunTransportFactory(null, $this->getHttpClient($config));

        if (empty($config['secret'])) {
            // Merge with the global services.mailgun defaults, but keep any
            // per-mailer domain/endpoint overrides that were already resolved.
            $globalConfig = (array) $this->app['config']->get('services.mailgun', []);
            $config = array_merge($globalConfig, array_filter([
                'domain' => $config['domain'] ?? null,
                'endpoint' => $config['endpoint'] ?? null,
                'scheme' => $config['scheme'] ?? null,
            ]));
        }

        if (empty($config['secret'])) {
            throw new \RuntimeException('Mailgun transport requires a secret (services.mailgun.secret).');
        }

        // Use the correct endpoint format for Mailgun API
        $endpoint = $config['endpoint'] ?? 'api.mailgun.net';
        $scheme = $config['scheme'] ?? 'https';

        return $factory->create(new Dsn(
            'mailgun+' . $scheme,
            $endpoint,
            $config['secret'],
            $config['domain'] ?? null
        ));
    }
}
