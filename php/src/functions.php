<?php

/*
 * API procedurale : une fonction par outil, sans manipuler d'objet.
 *
 *     it_tools_configure('pk_...', 'sk_...');
 *     $uuids = it_tools_uuid(count: 3);
 *     it_tools_pdf_merge(['a.pdf', 'b.pdf'])->save('merged.pdf');
 *
 * Sans appel a it_tools_configure(), la configuration est lue dans les variables
 * d'environnement IT_TOOLS_API_KEY, IT_TOOLS_API_SECRET, IT_TOOLS_BASE_URI et IT_TOOLS_VERIFY_PEER.
 */

declare(strict_types=1);

use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Config;
use Plateformweb\ItTools\File;
use Plateformweb\ItTools\Model\ProcessedFile;
use Plateformweb\ItTools\SharedClient;
use Plateformweb\ItTools\Transport\TransportInterface;

if (!function_exists('it_tools_configure')) {
    /**
     * Definit le client partage par toutes les fonctions it_tools_*.
     */
    function it_tools_configure(
        string $apiKey,
        ?string $apiSecret = null,
        string $baseUri = Config::DEFAULT_BASE_URI,
        float $timeout = 30.0,
        bool $verifyPeer = true,
        ?TransportInterface $transport = null,
    ): Client {
        return SharedClient::set(Client::create($apiKey, $apiSecret, $baseUri, $timeout, $verifyPeer, $transport));
    }

    /**
     * Renvoie le client partage, construit depuis l'environnement si aucun n'a ete configure.
     */
    function it_tools_client(): Client
    {
        return SharedClient::get();
    }

    // --- Outils developpeur -------------------------------------------------

    function it_tools_json_format(string|array $json, ?int $indent = null): array
    {
        return it_tools_client()->dev()->jsonFormat($json, $indent);
    }

    function it_tools_json_minify(string|array $json): array
    {
        return it_tools_client()->dev()->jsonMinify($json);
    }

    function it_tools_json_yaml(string $input, ?string $direction = null, ?int $indent = null): array
    {
        return it_tools_client()->dev()->jsonYaml($input, $direction, $indent);
    }

    function it_tools_json_to_csv(string|array $json, ?string $separator = null, ?bool $headers = null, ?bool $neutralizeFormulas = null): array
    {
        return it_tools_client()->dev()->jsonToCsv($json, $separator, $headers, $neutralizeFormulas);
    }

    function it_tools_jwt_decode(string $token): array
    {
        return it_tools_client()->dev()->jwtDecode($token);
    }

    function it_tools_ipv4_subnet(string $cidr): array
    {
        return it_tools_client()->dev()->ipv4Subnet($cidr);
    }

    function it_tools_chmod(?string $octal = null, ?string $symbolic = null, ?array $permissions = null): array
    {
        return it_tools_client()->dev()->chmod($octal, $symbolic, $permissions);
    }

    function it_tools_url_parse(string $url): array
    {
        return it_tools_client()->dev()->urlParse($url);
    }

    function it_tools_slugify(string $text, ?string $separator = null, ?bool $lowercase = null): array
    {
        return it_tools_client()->dev()->slugify($text, $separator, $lowercase);
    }

    function it_tools_case(string $text, ?string $case = null): array
    {
        return it_tools_client()->dev()->convertCase($text, $case);
    }

    function it_tools_text_statistics(string $text): array
    {
        return it_tools_client()->dev()->textStatistics($text);
    }

    function it_tools_html_entities(string $text, ?string $mode = null, ?bool $encodeAll = null): array
    {
        return it_tools_client()->dev()->htmlEntities($text, $mode, $encodeAll);
    }

    function it_tools_base64(string $text, ?string $mode = null, ?bool $urlSafe = null): array
    {
        return it_tools_client()->dev()->base64($text, $mode, $urlSafe);
    }

    function it_tools_hash(string $text, ?array $algorithms = null): array
    {
        return it_tools_client()->dev()->hash($text, $algorithms);
    }

    function it_tools_uuid(?int $count = null, ?string $version = null, ?bool $uppercase = null, ?bool $withoutDashes = null): array
    {
        return it_tools_client()->dev()->uuid($count, $version, $uppercase, $withoutDashes);
    }

    function it_tools_password(
        ?int $count = null,
        ?int $length = null,
        ?bool $lowercase = null,
        ?bool $uppercase = null,
        ?bool $numbers = null,
        ?bool $symbols = null,
        ?bool $excludeSimilar = null,
    ): array {
        return it_tools_client()->dev()->password($count, $length, $lowercase, $uppercase, $numbers, $symbols, $excludeSimilar);
    }

    function it_tools_token(?int $count = null, ?int $length = null, ?string $format = null, ?string $custom = null): array
    {
        return it_tools_client()->dev()->token($count, $length, $format, $custom);
    }

    function it_tools_timestamp(int|float|string|null $value = null, ?string $timezone = null): array
    {
        return it_tools_client()->dev()->timestamp($value, $timezone);
    }

    function it_tools_date_add(string $date, int $value, ?string $unit = null): array
    {
        return it_tools_client()->dev()->dateAdd($date, $value, $unit);
    }

    function it_tools_date_diff(string $from, string $to): array
    {
        return it_tools_client()->dev()->dateDiff($from, $to);
    }

    function it_tools_fake_data_fields(): array
    {
        return it_tools_client()->dev()->fakeDataFields();
    }

    function it_tools_fake_data(array $fields, ?int $count = null, ?string $locale = null, ?int $seed = null): array
    {
        return it_tools_client()->dev()->fakeData($fields, $count, $locale, $seed);
    }

    function it_tools_lorem(
        ?string $mode = null,
        ?int $count = null,
        ?int $minWords = null,
        ?int $maxWords = null,
        ?bool $startWithLorem = null,
        ?string $format = null,
        ?int $seed = null,
    ): array {
        return it_tools_client()->dev()->lorem($mode, $count, $minWords, $maxWords, $startWithLorem, $format, $seed);
    }

    function it_tools_color(string|array $color): array
    {
        return it_tools_client()->dev()->color($color);
    }

    // --- PDF ----------------------------------------------------------------

    function it_tools_pdf_merge(array $pdfs): ProcessedFile
    {
        return it_tools_client()->pdf()->merge($pdfs);
    }

    function it_tools_pdf_split(File|string $pdf, ?string $mode = null, ?string $ranges = null): ProcessedFile
    {
        return it_tools_client()->pdf()->split($pdf, $mode, $ranges);
    }

    function it_tools_pdf_delete_pages(File|string $pdf, string $pages): ProcessedFile
    {
        return it_tools_client()->pdf()->deletePages($pdf, $pages);
    }

    function it_tools_pdf_repair(File|string $pdf): ProcessedFile
    {
        return it_tools_client()->pdf()->repair($pdf);
    }

    function it_tools_pdf_compress(File|string $pdf, ?string $level = null): ProcessedFile
    {
        return it_tools_client()->pdf()->compress($pdf, $level);
    }

    function it_tools_pdf_to_images(File|string $pdf, ?int $dpi = null, ?string $format = null): ProcessedFile
    {
        return it_tools_client()->pdf()->toImages($pdf, $dpi, $format);
    }

    function it_tools_pdf_rotate(File|string $pdf, int|string|null $rotation = null, ?string $pageTarget = null, ?string $pages = null): ProcessedFile
    {
        return it_tools_client()->pdf()->rotate($pdf, $rotation, $pageTarget, $pages);
    }

    function it_tools_pdf_protect(File|string $pdf, string $userPassword, ?string $ownerPassword = null, ?bool $allowPrint = null, ?bool $allowCopy = null): ProcessedFile
    {
        return it_tools_client()->pdf()->protect($pdf, $userPassword, $ownerPassword, $allowPrint, $allowCopy);
    }

    function it_tools_pdf_unlock(File|string $pdf, ?string $password = null): ProcessedFile
    {
        return it_tools_client()->pdf()->unlock($pdf, $password);
    }

    function it_tools_pdf_edit(File|string $pdf, ?string $action = null, ?string $pages = null): ProcessedFile
    {
        return it_tools_client()->pdf()->edit($pdf, $action, $pages);
    }

    function it_tools_pdf_watermark(
        File|string $pdf,
        ?string $type = null,
        ?string $text = null,
        ?int $fontSize = null,
        ?int $opacity = null,
        File|string|null $watermarkImage = null,
    ): ProcessedFile {
        return it_tools_client()->pdf()->addWatermark($pdf, $type, $text, $fontSize, $opacity, $watermarkImage);
    }

    function it_tools_pdf_page_numbers(File|string $pdf, ?string $position = null, ?int $startFrom = null, ?int $fontSize = null): ProcessedFile
    {
        return it_tools_client()->pdf()->addPageNumbers($pdf, $position, $startFrom, $fontSize);
    }

    function it_tools_pdf_sign(
        File|string $pdf,
        ?string $signatureType = null,
        File|string|null $signatureImage = null,
        ?string $signatureText = null,
        ?int $page = null,
    ): ProcessedFile {
        return it_tools_client()->pdf()->sign($pdf, $signatureType, $signatureImage, $signatureText, $page);
    }

    function it_tools_pdf_info(File|string $pdf): array
    {
        return it_tools_client()->pdf()->info($pdf);
    }

    // --- CERFA --------------------------------------------------------------

    function it_tools_cerfa_catalog(): array
    {
        return it_tools_client()->cerfa()->catalog();
    }

    function it_tools_cerfa_fields(string $cerfaId): array
    {
        return it_tools_client()->cerfa()->fields($cerfaId);
    }

    function it_tools_cerfa_template(string $cerfaId): array
    {
        return it_tools_client()->cerfa()->template($cerfaId);
    }

    function it_tools_cerfa_generate(string $cerfaId, array $data): ProcessedFile
    {
        return it_tools_client()->cerfa()->generate($cerfaId, $data);
    }

    // --- QR codes, montants, images, Office ---------------------------------

    function it_tools_qrcode(string $content, ?string $type = null, ?string $format = null, ?int $size = null): array
    {
        return it_tools_client()->qrCode()->generate($content, $type, $format, $size);
    }

    function it_tools_number_to_words(int|float|string $amount, ?string $currency = null): array
    {
        return it_tools_client()->tools()->numberToWords($amount, $currency);
    }

    function it_tools_image_convert(File|string $file, ?string $format = null, ?int $quality = null, ?int $maxWidth = null): string
    {
        return it_tools_client()->tools()->convertImage($file, $format, $quality, $maxWidth);
    }

    function it_tools_office_to_pdf(File|string $file): ProcessedFile
    {
        return it_tools_client()->tools()->officeToPdf($file);
    }

    function it_tools_pdf_to_word(File|string $file): ProcessedFile
    {
        return it_tools_client()->tools()->pdfToWord($file);
    }
}
