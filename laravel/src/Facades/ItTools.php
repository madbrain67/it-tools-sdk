<?php

declare(strict_types=1);

namespace Plateformweb\ItToolsLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use Plateformweb\ItTools\Api\BookletApi;
use Plateformweb\ItTools\Api\CerfaApi;
use Plateformweb\ItTools\Api\DevToolsApi;
use Plateformweb\ItTools\Api\ImageKitApi;
use Plateformweb\ItTools\Api\PdfApi;
use Plateformweb\ItTools\Api\QrCodeApi;
use Plateformweb\ItTools\Api\ToolsApi;
use Plateformweb\ItTools\Config;

/**
 * @method static DevToolsApi dev()
 * @method static PdfApi pdf()
 * @method static CerfaApi cerfa()
 * @method static QrCodeApi qrCode()
 * @method static BookletApi booklet()
 * @method static ImageKitApi imageKit()
 * @method static ToolsApi tools()
 * @method static Config config()
 *
 * @see \Plateformweb\ItTools\Client
 */
final class ItTools extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'it-tools';
    }
}
