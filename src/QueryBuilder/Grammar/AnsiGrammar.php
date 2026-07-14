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
 * Gramática SQL estándar (ANSI): cita identificadores con comillas dobles.
 *
 * Es la gramática por defecto de la capa de base de datos, válida para PostgreSQL y
 * SQLite. Los drivers que necesiten un dialecto distinto (ej: MySQL con backticks)
 * extienden esta clase y sobreescriben lo que corresponda.
 */
class AnsiGrammar extends Grammar {

    /**
     * {@inheritdoc}
     *
     * Cita con comillas dobles, escapando las comillas dobles internas duplicándolas.
     */
    protected function wrapSegment(string $segment): string {
        return '"' . str_replace('"', '""', $segment) . '"';
    }
}
