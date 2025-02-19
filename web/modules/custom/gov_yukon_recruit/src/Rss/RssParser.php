<?php

namespace Drupal\gov_yukon_recruit\Rss;

use GuzzleHttp\Client;

/**
 * Parses an RSS feed as needed by the site.
 */
class RssParser {

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   HTTP client.
   */
  public function __construct(Client $client) {
    $this->httpClient = $client;
  }

  /**
   * Parse an URL that expected to be an RSS XML document.
   *
   * @param string $url
   *   RSS XML URL.
   *
   * @return \SimpleXMLElement
   *   XML object.
   */
  public function parse(string $url): \SimpleXMLElement {
    if (empty($url)) {
      throw new \InvalidArgumentException('Missing RSS URL.');
    }

    $response = (string) $this->httpClient->get($url)->getBody();

    if (empty($response) || strpos($response, '<rss') === FALSE) {
      throw new \RuntimeException('URL is not returning an RSS feed.');
    }

    $xml = simplexml_load_string($response);

    if (!($xml instanceof \SimpleXMLElement)) {
      throw new \RuntimeException('RSS document is invalid.');
    }

    return $xml;
  }

  /**
   * Parse the RSS feed and gather the titles and links.
   *
   * @param string $url
   *   RSS URL.
   *
   * @return array
   *   Array of title and link data for each item in the feed.
   */
  public function getTitlesAndLinks(string $url) {
    $xml   = $this->parse($url);
    $data  = [];
    $items = $xml->channel->item;
    foreach ($items as $item) {
      if (empty($item->title) || empty($item->link)) {
        continue;
      }
      $data[] = [
        'title' => (string) $item->title,
        'url'   => (string) $item->link,
      ];
    }

    return $data;
  }

}
