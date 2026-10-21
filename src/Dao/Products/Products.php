<?php

namespace Dao\Products;

use Dao\Table;

class Products extends Table
{
    public static function getProducts(
        string $partialName = '',
        string $status = '',
        string $orderBy = '',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ) {
        $sqlstr = "SELECT p.productId, p.productName, p.productDescription, p.productPrice, p.productImgUrl, p.productStatus, case when p.productStatus = 'ACT' then 'Activo' when p.productStatus = 'INA' then 'Inactivo' else 'Sin Asignar' end as productStatusDsc 
    FROM products p";
        $sqlstrCount = 'SELECT COUNT(*) as count FROM products p';
        $conditions = [];
        $params = [];
        if ($partialName != '') {
            $conditions[] = 'p.productName LIKE :partialName';
            $params['partialName'] = '%'.$partialName.'%';
        }
        if (!in_array($status, ['ACT', 'INA', ''])) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }
        if ($status != '') {
            $conditions[] = 'p.productStatus = :status';
            $params['status'] = $status;
        }
        if (count($conditions) > 0) {
            $sqlstr .= ' WHERE '.implode(' AND ', $conditions);
            $sqlstrCount .= ' WHERE '.implode(' AND ', $conditions);
        }
        if (!in_array($orderBy, ['productId', 'productName', 'productPrice', ''])) {
            throw new \Exception('Error Processing Request OrderBy has invalid value');
        }
        if ($orderBy != '') {
            $sqlstr .= ' ORDER BY '.$orderBy;
            if ($orderDescending) {
                $sqlstr .= ' DESC';
            }
        }
        $itemsPerPage = $itemsPerPage > 0 ? $itemsPerPage : 10;
        $page = $page >= 0 ? $page : 0;
        $numeroDeRegistros = self::obtenerUnRegistro($sqlstrCount, $params)['count'];
        $pagesCount = $numeroDeRegistros > 0 ? ceil($numeroDeRegistros / $itemsPerPage) : 1;
        if ($page > $pagesCount - 1) {
            $page = $pagesCount - 1;
        }
        $offset = $page * $itemsPerPage;
        $sqlstr .= ' LIMIT '.$offset.', '.$itemsPerPage;

        $registros = self::obtenerRegistros($sqlstr, $params);

        return ['products' => $registros, 'total' => $numeroDeRegistros, 'page' => $page, 'itemsPerPage' => $itemsPerPage];
    }

    public static function getProductById(int $productId)
    {
        $sqlstr = 'SELECT p.productId, p.productName, p.productDescription, p.productPrice, p.productImgUrl, p.productStatus FROM products p WHERE p.productId = :productId';
        $params = ['productId' => $productId];

        return self::obtenerUnRegistro($sqlstr, $params);
    }

    public static function insertProduct(
        string $productName,
        string $productDescription,
        float $productPrice,
        string $productImgUrl,
        string $productStatus
    ) {
        $sqlstr = 'INSERT INTO products (productName, productDescription, productPrice, productImgUrl, productStatus) VALUES (:productName, :productDescription, :productPrice, :productImgUrl, :productStatus)';
        $params = [
            'productName' => $productName,
            'productDescription' => $productDescription,
            'productPrice' => $productPrice,
            'productImgUrl' => $productImgUrl,
            'productStatus' => $productStatus,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function updateProduct(
        int $productId,
        string $productName,
        string $productDescription,
        float $productPrice,
        string $productImgUrl,
        string $productStatus
    ) {
        $sqlstr = 'UPDATE products SET productName = :productName, productDescription = :productDescription, productPrice = :productPrice, productImgUrl = :productImgUrl, productStatus = :productStatus WHERE productId = :productId';
        $params = [
            'productId' => $productId,
            'productName' => $productName,
            'productDescription' => $productDescription,
            'productPrice' => $productPrice,
            'productImgUrl' => $productImgUrl,
            'productStatus' => $productStatus,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function deleteProduct(int $productId)
    {
        $sqlstr = 'DELETE FROM products WHERE productId = :productId';
        $params = ['productId' => $productId];

        return self::executeNonQuery($sqlstr, $params);
    }
}
