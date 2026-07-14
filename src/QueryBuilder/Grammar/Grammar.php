<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Database\QueryBuilder\Grammar;

/**
 * Gramática SQL: traduce las partes estructurales de una consulta al dialecto concreto.
 *
 * Cada motor (MySQL, PostgreSQL, SQLite) tiene diferencias sintácticas — el carácter de
 * citado de identificadores, si soporta `RETURNING`, si permite `DELETE ... LIMIT`, etc.
 * Esta clase concentra esas diferencias para que el QueryBuilder y las cláusulas generen
 * SQL correcto sin conocer el motor.
 *
 * Principio de compatibilidad: solo se citan identificadores en posiciones donde el
 * framework sabe con certeza que hay un identificador (nombres de tabla, listas de
 * columnas de INSERT/UPDATE, y columnas simples de SELECT/GROUP/ORDER). El SQL libre
 * que escribe el desarrollador (condiciones WHERE/HAVING, ON de JOIN, expresiones con
 * funciones) se respeta verbatim.
 */
abstract class Grammar {

    /**
     * Cita un identificador o expresión de columna aplicando heurística.
     *
     * - `*` se deja sin tocar.
     * - `col AS alias` (o `col as alias`) cita cada lado por separado.
     * - Cualquier expresión con paréntesis, espacios u operadores (ej: `COUNT(*)`,
     *   `created_at DESC`) se considera SQL crudo y pasa sin modificar.
     * - Un identificador punteado (`tabla.columna`) se cita por segmento.
     *
     * @param string $value Identificador o expresión a citar
     * @return string Valor citado según el dialecto
     */
    public function wrap(string $value): string {
        $value = trim($value);

        if ($value === '*') {
            return '*';
        }

        // Alias explícito: "expr AS alias"
        if (preg_match('/\s+as\s+/i', $value)) {
            $parts = preg_split('/\s+as\s+/i', $value, 2);
            return $this->wrap(trim($parts[0])) . ' AS ' . $this->wrapSegment(trim($parts[1]));
        }

        // Solo se cita si es un identificador punteado "puro": cada segmento es un nombre
        // válido o `*` (ej: `col`, `tabla.col`, `alias.*`). Cualquier otra cosa —funciones,
        // operadores, espacios, direcciones de orden— es SQL crudo y pasa sin modificar.
        if (!$this->isPlainIdentifier($value)) {
            return $value;
        }

        return $this->wrapDotted($value);
    }

    /**
     * Cita una referencia de tabla, opcionalmente calificada por schema y con alias.
     *
     * @param string $table Nombre de tabla, posiblemente `schema.tabla`
     * @param string|null $alias Alias opcional
     * @return string Referencia de tabla citada
     */
    public function wrapTable(string $table, ?string $alias = null): string {
        // Un subquery usado como tabla (`(SELECT ...) AS x`) o cualquier expresión que no
        // sea un identificador punteado puro pasa sin citar.
        $sql = $this->isPlainIdentifier($table) ? $this->wrapDotted($table) : $table;

        if ($alias !== null && $alias !== '') {
            $sql .= ' AS ' . $this->wrapSegment($alias);
        }

        return $sql;
    }

    /**
     * Cita una lista de columnas y las une con comas.
     *
     * @param array $columns Columnas a citar
     * @return string Lista citada separada por comas
     */
    public function columnize(array $columns): string {
        return implode(', ', array_map($this->wrap(...), $columns));
    }

    /**
     * Compila la cláusula LIMIT/OFFSET.
     *
     * @param int|null $limit Límite de filas
     * @param int|null $offset Desplazamiento
     * @return string Fragmento SQL de LIMIT/OFFSET (o cadena vacía)
     */
    public function compileLimit(?int $limit, ?int $offset): string {
        $parts = [];

        if ($limit !== null) {
            $parts[] = "LIMIT $limit";
        }

        if ($offset !== null) {
            $parts[] = "OFFSET $offset";
        }

        return implode(' ', $parts);
    }

    /**
     * Indica si el motor soporta la cláusula `RETURNING` en INSERT/UPDATE/DELETE.
     *
     * @return bool Verdadero si soporta RETURNING
     */
    public function supportsReturning(): bool {
        return false;
    }

    /**
     * Compila una cláusula `RETURNING` para las columnas dadas.
     *
     * @param array $columns Columnas a devolver
     * @return string Fragmento `RETURNING ...`
     */
    public function compileReturning(array $columns): string {
        return 'RETURNING ' . $this->columnize($columns);
    }

    /**
     * Indica si el motor soporta `DELETE ... LIMIT`.
     *
     * MySQL y SQLite lo permiten; PostgreSQL no.
     *
     * @return bool Verdadero si soporta DELETE con LIMIT
     */
    public function supportsDeleteLimit(): bool {
        return true;
    }

    /**
     * Determina si un valor es un identificador punteado puro (cada segmento es un
     * nombre válido o `*`), apto para citar. Ej: `col`, `tabla.col`, `alias.*`.
     *
     * @param string $value Valor a evaluar
     * @return bool Verdadero si es un identificador citable
     */
    protected function isPlainIdentifier(string $value): bool {
        return (bool)preg_match('/^([A-Za-z_][A-Za-z0-9_]*|\*)(\.([A-Za-z_][A-Za-z0-9_]*|\*))*$/', $value);
    }

    /**
     * Cita un identificador punteado (`a.b.c`), dejando `*` sin citar.
     *
     * @param string $value Identificador, posiblemente punteado
     * @return string Identificador citado por segmento
     */
    protected function wrapDotted(string $value): string {
        $segments = array_map(
            fn(string $segment): string => $segment === '*' ? '*' : $this->wrapSegment($segment),
            explode('.', $value)
        );

        return implode('.', $segments);
    }

    /**
     * Cita un único segmento de identificador con el carácter propio del dialecto.
     *
     * @param string $segment Segmento a citar (nombre de tabla, columna, schema o alias)
     * @return string Segmento citado
     */
    abstract protected function wrapSegment(string $segment): string;
}
