<?php

function test_http_get_request()
{
    $request = new HttpRequest();
    $raw = "GET /path?x=1&y=two HTTP/1.1\r\n"
         . "Host: example.test\r\n"
         . "Cookie: a=b; c=d\r\n"
         . "\r\n";

    assertTrue($request->handleInput($raw));
    assertSame('GET', $request->SERVER['REQUEST_METHOD']);
    assertSame('/path', $request->SERVER['SCRIPT_NAME']);
    assertSame('1.1', $request->SERVER['httpVersion']);
    assertSame('1', $request->GET['x']);
    assertSame('two', $request->GET['y']);
    assertSame('b', $request->COOKIE['a']);
    assertSame('d', $request->COOKIE['c']);
}

function test_http_post_urlencoded()
{
    $body = 'p=hello&q=world';
    $request = new HttpRequest();
    $raw = "POST /submit HTTP/1.1\r\n"
         . "Host: example.test\r\n"
         . "Content-Type: application/x-www-form-urlencoded\r\n"
         . "Content-Length: " . strlen($body) . "\r\n"
         . "\r\n"
         . $body;

    assertTrue($request->handleInput($raw));
    assertSame('hello', $request->POST['p']);
    assertSame('world', $request->POST['q']);
}

function test_http_response_headers()
{
    $response = new HttpResponse('1.1', 200);
    $response->addHeader('Content-Type: text/plain');
    $response->addBody('hi');

    $headers = $response->getHeaders();
    assertTrue(strpos($headers, 'HTTP/1.1 200 OK') === 0);
    assertTrue(strpos($headers, "Content-Type: text/plain\r\n") !== false);
    assertTrue(strpos($headers, "Content-Length: 2\r\n") !== false);

    $response2 = new HttpResponse('1.1', 404);
    assertTrue(strpos($response2->getHeaders(), 'HTTP/1.1 404 File Not Found') === 0);
}

function test_http_response_duplicate_header()
{
    $response = new HttpResponse('1.1', 200);
    $response->addHeader('X-Test: one');
    $response->addHeader('x-test: two');
    $response->addHeader('X-Other: three');
    $headers = $response->getHeaders();
    assertTrue(stripos($headers, 'x-test: two') !== false);
    assertTrue(stripos($headers, 'x-test: one') === false);
}

class PrismTestHttpHandler
{
    public $docRoot;

    public function __construct($docRoot)
    {
        $this->docRoot = $docRoot;
    }

    public function getDocRoot()
    {
        return $this->docRoot;
    }
}

function test_php_parser_documented_page_api()
{
    global $PRISM;

    $dir = sys_get_temp_dir() . '/prism-page-' . uniqid();
    mkdir($dir);
    $page = $dir . '/page.php';
    file_put_contents($page, '<?php
$_RESPONSE->addHeader("X-Test: yes");
echo "GET=" . $_GET["x"] . ";POST=" . $_POST["p"] . ";SERVER=" . $SERVER["REMOTE_ADDR"] . ";";
');

    $oldHttp = isset($PRISM->http) ? $PRISM->http : null;
    $PRISM->http = new PrismTestHttpHandler($dir);

    $SERVER = array(
        'REMOTE_ADDR' => '127.0.0.1',
        'SERVER_NAME' => 'example.test',
        'REQUEST_METHOD' => 'POST',
        'httpVersion' => '1.1',
    );
    $GET = array('x' => 'one');
    $POST = array('p' => 'two');
    $COOKIE = array();
    $FILES = array();

    $response = new HttpResponse('1.1', 200);
    $html = PHPParser::parseFile($response, '/page.php', $SERVER, $GET, $POST, $COOKIE, $FILES);

    assertSame('GET=one;POST=two;SERVER=127.0.0.1;', $html);
    assertSame('yes', $response->getHeader('X-Test'));
    assertSame(ROOTPATH, getcwd());

    unlink($page);
    rmdir($dir);
    $PRISM->http = $oldHttp;
}

return array(
    'http/get-request'                 => 'test_http_get_request',
    'http/post-urlencoded'             => 'test_http_post_urlencoded',
    'http/response-headers'            => 'test_http_response_headers',
    'http/response-duplicate-header'   => 'test_http_response_duplicate_header',
    'http/php-parser-documented-api'   => 'test_php_parser_documented_page_api',
);
