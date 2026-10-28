<?php

declare(strict_types=1);

namespace Einvoicing;

use Composer\InstalledVersions;
use Einvoicing\Exceptions\NoHttpClientException;
use Einvoicing\Exceptions\Problems;
use Einvoicing\Exceptions\TransportException;
use Einvoicing\Resources\Account;
use Einvoicing\Resources\Billing;
use Einvoicing\Resources\Conversions;
use Einvoicing\Resources\Keys;
use Einvoicing\Resources\Participants;
use Einvoicing\Resources\Rulesets;
use Einvoicing\Resources\Usage;
use Einvoicing\Resources\Validations;
use Http\Discovery\Exception as DiscoveryException;
use JustSteveKing\SdkToolbox\Auth\BearerToken;
use JustSteveKing\SdkToolbox\Connector;
use JustSteveKing\SdkToolbox\Errors\TransportException as NoResponse;
use JustSteveKing\SdkToolbox\Requests\Request;
use JustSteveKing\SdkToolbox\Retries\RetryPolicy;
use JustSteveKing\SdkToolbox\Retries\RetryStrategy;
use OutOfBoundsException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

/**
 * The einvoicing.dev API.
 *
 * Bring your own HTTP, but do not wire it up unless you want to:
 *
 *     $client = new Client($key);
 *
 * The PSR-18 client and PSR-17 factories are discovered from whatever is
 * installed, so this works with Guzzle, Symfony, curl or whatever your
 * framework already has, and still brings no implementation of its own.
 * Pass any of them explicitly to override the discovery — a framework's
 * container should, and the tests here do.
 *
 * Built on juststeveking/sdk-toolbox, which brings retries that only repeat
 * what is safe to repeat, RFC 9457 problems read properly, and credentials
 * kept out of every exception and log line.
 */
final class Client extends Connector
{
    /**
     * What to call this client when composer cannot say.
     *
     * Only a fallback: the User-Agent reports the installed version, because
     * a hand-maintained constant drifts. This one had been claiming 0.1.0
     * since three releases earlier.
     */
    public const string VERSION = '0.2.4';

    public function __construct(
        #[SensitiveParameter]
        string $key,
        ?ClientInterface $http = null,
        ?RequestFactoryInterface $requests = null,
        ?StreamFactoryInterface $streams = null,
        string $baseUrl = 'https://api.einvoicing.dev',
        RetryStrategy $retry = new RetryPolicy,
        ?LoggerInterface $logger = null,
    ) {
        // Discovery throws when nothing is installed. That is a composer
        // problem, not a runtime one, so say which packages are missing
        // rather than letting php-http's own wording reach the application.
        try {
            parent::__construct(
                baseUri: $baseUrl,
                auth: new BearerToken($key),
                http: $http,
                headers: ['Accept' => 'application/json'],
                userAgent: 'einvoicing-php/'.self::version(),
                exceptions: new Problems,
                requests: $requests,
                streams: $streams,
                // Plain HTTP only to this machine, for a local server.
                insecure: in_array(parse_url($baseUrl, PHP_URL_HOST), ['localhost', '127.0.0.1', '[::1]'], true),
                retry: $retry,
                logger: $logger,
            );
        } catch (DiscoveryException $e) {
            throw new NoHttpClientException(
                message: 'No PSR-18 HTTP client or PSR-17 factories were found. Install '
                    .'them — symfony/http-client and nyholm/psr7 will do — or pass '
                    .'your own to the constructor.',
                previous: $e,
            );
        }
    }

    public function validations(): Validations
    {
        return new Validations($this);
    }

    public function conversions(): Conversions
    {
        return new Conversions($this);
    }

    public function participants(): Participants
    {
        return new Participants($this);
    }

    public function rulesets(): Rulesets
    {
        return new Rulesets($this);
    }

    public function usage(): Usage
    {
        return new Usage($this);
    }

    public function keys(): Keys
    {
        return new Keys($this);
    }

    public function account(): Account
    {
        return new Account($this);
    }

    public function billing(): Billing
    {
        return new Billing($this);
    }

    /**
     * Every exception this package throws implements EinvoicingException,
     * a failure to get any answer included.
     *
     * @template TResult
     *
     * @param  Request<TResult>  $request
     * @return TResult
     */
    public function send(Request $request): mixed
    {
        try {
            return parent::send($request);
        } catch (NoResponse $e) {
            throw new TransportException($e->getMessage(), previous: $e);
        }
    }

    /**
     * The installed version, so the User-Agent says something true.
     *
     * Composer knows what it installed; this class does not. Inside this
     * repository that answers "dev-main", which is also true.
     */
    private static function version(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return self::VERSION;
        }

        try {
            $version = InstalledVersions::getPrettyVersion('einvoicing/sdk');
        } catch (OutOfBoundsException) {
            // Not installed as a package: a checkout, or a bespoke autoloader.
            return self::VERSION;
        }

        return $version === null ? self::VERSION : ltrim($version, 'v');
    }
}
