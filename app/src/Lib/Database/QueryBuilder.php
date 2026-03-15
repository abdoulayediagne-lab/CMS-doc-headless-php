<?php

namespace App\Lib\Database;

/**
 * QueryBuilder - Constructeur de requêtes SQL pour PostgreSQL 18
 * 
 * Supporte : SELECT, INSERT, UPDATE, DELETE
 * Jointures : INNER JOIN, LEFT JOIN, RIGHT JOIN, FULL JOIN
 * Fonctionnalités : WHERE, GROUP BY, ORDER BY, LIMIT, OFFSET
 * Sécurité : Prepared statements (PDO)
 * Debug : Affichage de la requête SQL générée avec ses paramètres
 */
class QueryBuilder
{
    private \PDO $pdo;

    private string $type = '';           
    private string $table = '';
    private array $columns = ['*'];
    private array $wheres = [];
    private array $params = [];
    private array $joins = [];
    private array $groupBy = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $insertData = [];
    private array $updateData = [];
    private ?string $subQuery = null;
    private ?string $subQueryAlias = null;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    
    private function reset(): void
    {
        $this->type = '';
        $this->table = '';
        $this->columns = ['*'];
        $this->wheres = [];
        $this->params = [];
        $this->joins = [];
        $this->groupBy = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->insertData = [];
        $this->updateData = [];
        $this->subQuery = null;
        $this->subQueryAlias = null;
    }

    // =========================================
    // SELECT
    // =========================================

    public function select(string $table, array $columns = ['*']): self
    {
        $this->reset();
        $this->type = 'SELECT';
        $this->table = $table;
        $this->columns = $columns;
        return $this;
    }

    /**
     * Ajoute une sous-requête dans le FROM
     * 
     * @param QueryBuilder $subQuery Le sous-query builder
     * @param string $alias Alias de la sous-requête
     * @return self
     */
    public function fromSubQuery(QueryBuilder $subQuery, string $alias): self
    {
        $this->subQuery = $subQuery->toSQL();
        $this->subQueryAlias = $alias;
        // Fusionne les paramètres de la sous-requête
        $this->params = array_merge($subQuery->getParams(), $this->params);
        return $this;
    }

    // =========================================
    // WHERE
    // =========================================

    public function where(string $column, string $operator, mixed $value = null): self
    {
        $upperOp = strtoupper(trim($operator));

        if ($upperOp === 'IS NULL' || $upperOp === 'IS NOT NULL') {
            $this->wheres[] = "$column $upperOp";
        } elseif ($upperOp === 'IN' && is_array($value)) {
            $placeholders = [];
            foreach ($value as $v) {
                $placeholder = ':w' . count($this->params);
                $placeholders[] = $placeholder;
                $this->params[$placeholder] = $v;
            }
            $this->wheres[] = "$column IN (" . implode(', ', $placeholders) . ")";
        } else {
            $placeholder = ':w' . count($this->params);
            $this->wheres[] = "$column $operator $placeholder";
            $this->params[$placeholder] = $value;
        }

        return $this;
    }

    
    public function orWhere(string $column, string $operator, mixed $value = null): self
    {
        $upperOp = strtoupper(trim($operator));

        if ($upperOp === 'IS NULL' || $upperOp === 'IS NOT NULL') {
            $this->wheres[] = "OR $column $upperOp";
        } else {
            $placeholder = ':w' . count($this->params);
            $this->wheres[] = "OR $column $operator $placeholder";
            $this->params[$placeholder] = $value;
        }

        return $this;
    }

    // =========================================
    // JOINTURES
    // =========================================

    /**
     * Ajoute un INNER JOIN
     */
    public function innerJoin(string $table, string $on): self
    {
        $this->joins[] = "INNER JOIN $table ON $on";
        return $this;
    }

    /**
     * Ajoute un LEFT JOIN
     */
    public function leftJoin(string $table, string $on): self
    {
        $this->joins[] = "LEFT JOIN $table ON $on";
        return $this;
    }

    /**
     * Ajoute un RIGHT JOIN (optionnel consigne)
     */
    public function rightJoin(string $table, string $on): self
    {
        $this->joins[] = "RIGHT JOIN $table ON $on";
        return $this;
    }

    /**
     * Ajoute un FULL JOIN (optionnel consigne)
     */
    public function fullJoin(string $table, string $on): self
    {
        $this->joins[] = "FULL JOIN $table ON $on";
        return $this;
    }

    // =========================================
    // GROUP BY, ORDER BY, LIMIT, OFFSET
    // =========================================


    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * Limite le nombre de résultats
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Définit l'offset (pagination)
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // =========================================
    // INSERT
    // =========================================

    public function insert(string $table, array $data): self
    {
        $this->reset();
        $this->type = 'INSERT';
        $this->table = $table;
        $this->insertData = $data;

        foreach ($data as $column => $value) {
            $placeholder = ':i_' . $column;
            $this->params[$placeholder] = $value;
        }

        return $this;
    }

    // =========================================
    // UPDATE
    // =========================================


    public function update(string $table, array $data): self
    {
        $this->reset();
        $this->type = 'UPDATE';
        $this->table = $table;
        $this->updateData = $data;

        foreach ($data as $column => $value) {
            $placeholder = ':u_' . $column;
            $this->params[$placeholder] = $value;
        }

        return $this;
    }


    
    public function delete(string $table): self
    {
        $this->reset();
        $this->type = 'DELETE';
        $this->table = $table;
        return $this;
    }

    public function toSQL(): string
    {
        return match ($this->type) {
            'SELECT' => $this->buildSelect(),
            'INSERT' => $this->buildInsert(),
            'UPDATE' => $this->buildUpdate(),
            'DELETE' => $this->buildDelete(),
            default => throw new \RuntimeException("Type de requête non défini. Utilisez select(), insert(), update() ou delete()."),
        };
    }

    private function buildSelect(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns);

        if ($this->subQuery && $this->subQueryAlias) {
            $sql .= " FROM ($this->subQuery) AS $this->subQueryAlias";
        } else {
            $sql .= " FROM $this->table";
        }

        foreach ($this->joins as $join) {
            $sql .= " $join";
        }

        $sql .= $this->buildWhere();

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT $this->limit";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET $this->offset";
        }

        return $sql;
    }

    private function buildInsert(): string
    {
        $columns = array_keys($this->insertData);
        $placeholders = array_map(fn($col) => ':i_' . $col, $columns);

        return "INSERT INTO $this->table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }

    private function buildUpdate(): string
    {
        $setClauses = [];
        foreach (array_keys($this->updateData) as $column) {
            $setClauses[] = "$column = :u_$column";
        }

        $sql = "UPDATE $this->table SET " . implode(', ', $setClauses);
        $sql .= $this->buildWhere();

        return $sql;
    }

    private function buildDelete(): string
    {
        $sql = "DELETE FROM $this->table";
        $sql .= $this->buildWhere();

        return $sql;
    }

    private function buildWhere(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = ' WHERE ';
        foreach ($this->wheres as $i => $clause) {
            if ($i === 0) {
                $sql .= ltrim($clause, 'OR ');
            } else {
                $sql .= str_starts_with($clause, 'OR ') ? " $clause" : " AND $clause";
            }
        }

        return $sql;
    }

    // =========================================
    // DEBUG (comme exigé)
    // =========================================

    /**
     * @return array ['sql' => string, 'params' => array, 'query_with_values' => string]
     */
    public function debug(): array
    {
        $sql = $this->toSQL();
        $queryWithValues = $sql;

        // Remplace les placeholders par les vraies valeurs pour l'affichage
        foreach ($this->params as $placeholder => $value) {
            $displayValue = is_string($value) ? "'$value'" : (is_null($value) ? 'NULL' : $value);
            $queryWithValues = str_replace($placeholder, (string) $displayValue, $queryWithValues);
        }

        $debug = [
            'sql' => $sql,
            'params' => $this->params,
            'query_with_values' => $queryWithValues,
        ];

        // Affiche dans la console
        echo "\n========== DEBUG SQL ==========\n";
        echo "Requête : $sql\n";
        echo "Paramètres : " . json_encode($this->params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "Requête complète : $queryWithValues\n";
        echo "================================\n\n";

        return $debug;
    }

    // =========================================
    // EXÉCUTION
    // =========================================

    
    public function fetchAll(): array
    {
        $stmt = $this->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    
    public function fetchOne(): ?array
    {
        $stmt = $this->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    
    public function run(): int
    {
        $stmt = $this->execute();
        return $stmt->rowCount();
    }

   
    public function getParams(): array
    {
        return $this->params;
    }

    
    private function execute(): \PDOStatement
    {
        $sql = $this->toSQL();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt;
    }
}