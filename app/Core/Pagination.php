<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pagination helper class
 */
final class Pagination
{
    public int $page;
    public int $perPage;
    public int $total;
    public int $offset;
    public int $totalPages;

    public function __construct(int $page = 1, int $perPage = 20)
    {
        $this->page = max(1, $page);
        $this->perPage = max(1, $perPage);
        $this->offset = ($this->page - 1) * $this->perPage;
        $this->total = 0;
        $this->totalPages = 0;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
        $this->totalPages = (int) ceil($total / $this->perPage);
        if ($this->page > $this->totalPages && $this->totalPages > 0) {
            $this->page = $this->totalPages;
            $this->offset = ($this->page - 1) * $this->perPage;
        }
    }

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    public function getRange(): array
    {
        $start = $this->offset + 1;
        $end = min($this->offset + $this->perPage, $this->total);
        return [$start, $end];
    }

    /**
     * Build pagination query string preserving other parameters
     */
    public static function buildQueryString(array $params, int $page): string
    {
        $params['page'] = $page;
        return http_build_query($params);
    }

    /**
     * Get page number from request
     */
    public static function getPageFromRequest(): int
    {
        $page = (int) ($_GET['page'] ?? 1);
        return max(1, $page);
    }

    /**
     * Render pagination component for view
     */
    public function render(string $baseUrl, array $queryParams = []): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        $html = '<nav aria-label="Paginação"><ul class="pagination justify-content-center">';

        // Previous button
        if ($this->hasPrevious()) {
            $query = self::buildQueryString($queryParams, $this->page - 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $query . '">Anterior</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
        }

        // Page numbers
        $startPage = max(1, $this->page - 2);
        $endPage = min($this->totalPages, $this->page + 2);

        if ($startPage > 1) {
            $query = self::buildQueryString($queryParams, 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $query . '">1</a></li>';
            if ($startPage > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i === $this->page) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $query = self::buildQueryString($queryParams, $i);
                $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $query . '">' . $i . '</a></li>';
            }
        }

        if ($endPage < $this->totalPages) {
            if ($endPage < $this->totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $query = self::buildQueryString($queryParams, $this->totalPages);
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $query . '">' . $this->totalPages . '</a></li>';
        }

        // Next button
        if ($this->hasNext()) {
            $query = self::buildQueryString($queryParams, $this->page + 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $query . '">Próxima</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Próxima</span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }
}
