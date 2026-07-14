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

namespace PhobosFramework\Database\Drivers;

use PDO;
use PhobosFramework\Database\QueryBuilder\Grammar\Grammar;

/**
 * Interface que deben implementar todos los controladores de base de datos.
 * Define los métodos necesarios para interactuar con diferentes sistemas
 * de gestión de bases de datos de manera uniforme.
 */
interface DriverInterface {
    /**
     * Obtiene la gramática SQL del dialecto que maneja este controlador.
     *
     * La gramática traduce las partes estructurales de una consulta (citado de
     * identificadores, LIMIT, RETURNING, etc.) al dialecto concreto del motor.
     *
     * @return Grammar Gramática específica del dialecto
     */
    public function getGrammar(): Grammar;

    /**
     * Obtiene el DSN (Data Source Name) para la conexión PDO.
     * El DSN es una cadena que contiene la información necesaria para conectar
     * con la base de datos específica.
     *
     * @param array $config Arreglo con la configuración de conexión (host, puerto, nombre de BD, etc.)
     * @return string Cadena DSN formateada según el driver específico
     */
    public function getDSN(array $config): string;

    /**
     * Obtiene las opciones PDO específicas del controlador.
     * Cada controlador puede requerir diferentes opciones de configuración
     * para optimizar la conexión.
     *
     * @param array $config Arreglo con la configuración de conexión
     * @return array Arreglo de opciones PDO específicas del controlador
     */
    public function getPDOOptions(array $config): array;

    /**
     * Configura la conexión después de establecerla.
     * Permite realizar configuraciones adicionales específicas del controlador
     * una vez que la conexión está establecida.
     *
     * @param PDO $pdo Instancia PDO ya conectada a la base de datos
     * @param array $config Arreglo con la configuración de conexión
     * @return void
     */
    public function configure(PDO $pdo, array $config): void;

    /**
     * Obtiene el nombre del controlador.
     * Identifica el tipo de sistema de base de datos que maneja este controlador.
     *
     * @return string Nombre identificativo del controlador
     */
    public function getName(): string;

    /**
     * Verifica si el controlador soporta puntos de guardado (savepoints).
     * Los savepoints permiten crear puntos de control dentro de una transacción.
     *
     * @return bool Verdadero si soporta savepoints, falso en caso contrario
     */
    public function supportsSavepoints(): bool;

    /**
     * Obtiene la sintaxis SQL para crear un punto de guardado (savepoint).
     * Genera la sentencia SQL específica del controlador para crear un savepoint.
     *
     * @param string $name Nombre que identificará al punto de guardado
     * @return string Sentencia SQL para crear el savepoint
     */
    public function getSavepointSQL(string $name): string;

    /**
     * Obtiene la sintaxis SQL para revertir hasta un punto de guardado (savepoint).
     * Genera la sentencia SQL específica del controlador para hacer rollback a un savepoint.
     *
     * @param string $name Nombre del punto de guardado al que se quiere revertir
     * @return string Sentencia SQL para hacer rollback al savepoint
     */
    public function getRollbackSavepointSQL(string $name): string;

    /**
     * Obtiene la sintaxis SQL para liberar un punto de guardado (savepoint).
     * Genera la sentencia SQL específica del controlador para eliminar un savepoint.
     *
     * @param string $name Nombre del punto de guardado que se desea liberar
     * @return string Sentencia SQL para liberar el savepoint
     */
    public function getReleaseSavepointSQL(string $name): string;

    /**
     * Obtiene la sintaxis SQL para establecer el nivel de aislamiento de transacción.
     * Define cómo las transacciones interactúan entre sí en la base de datos.
     *
     * @param string $level Nivel de aislamiento deseado (READ UNCOMMITTED, READ COMMITTED, etc.)
     * @return string Sentencia SQL para establecer el nivel de aislamiento
     */
    public function getSetIsolationLevelSQL(string $level): string;

    /**
     * Obtiene el ID del último registro insertado.
     * Maneja diferentes métodos según el controlador, ya que no todos utilizan lastInsertId.
     *
     * @param PDO $pdo Instancia PDO conectada
     * @param string|null $sequence Nombre de la secuencia (necesario en PostgreSQL)
     * @return string|false El ID del último registro insertado o falso si hubo error
     */
    public function getLastInsertId(PDO $pdo, ?string $sequence = null): string|false;

    /**
     * Escapa un identificador (tabla, columna, etc.) según las reglas del controlador.
     * Previene problemas de sintaxis SQL y posibles inyecciones SQL.
     *
     * @param string $identifier Nombre del identificador que se desea escapar
     * @return string Identificador escapado según las reglas del controlador
     */
    public function quoteIdentifier(string $identifier): string;
}
