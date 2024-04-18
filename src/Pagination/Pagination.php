<?php

namespace App\Pagination;

use Symfony\Component\HttpFoundation\Request;
use function basename;
use function explode;
use function sprintf;

final class Pagination
{
    public const ITEMS_PER_PAGE = 10;

    private Request $request;

    public function getVars(int $totalItems, Request $request): array
    {
        $this->request = $request;
        $pages = [];
        $currentPage = (int) basename($this->request->getPathInfo());

        $nbItems = $totalItems / self::ITEMS_PER_PAGE;
        $nbItems = (int) $nbItems + $totalItems % self::ITEMS_PER_PAGE;

        for ($i = 1; $i <= $nbItems; $i++) {
            $pages[$i] = [
                "status" => $i === $currentPage ? 'inactive' : 'active',
                "url" => $i === $currentPage ? null : $this->setUrl($i)
            ];
        }

        return $pages;
    }

    private function setUrl(int $page): string
    {
        return sprintf(
            'http://%s/%s/%d',
            $this->request->getHttpHost(),
            explode('/', $this->request->getPathInfo())[1],
            $page
        );
    }
}
