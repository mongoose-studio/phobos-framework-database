# Changelog

Todos los cambios relevantes de la capa de datos de Phobos se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el proyecto
se adhiere a [Versionado Semántico](https://semver.org/lang/es/).

## [3.2.1] - 2026-07-14

### Corregido

- **`QueryBuilder::fetchColumn()` reventaba cuando la conexión usa `PDO::FETCH_OBJ`.**
  Indexaba la fila como array (`$fila[$columna]`), pero con `FETCH_OBJ` la fila llega como
  `stdClass`, y PHP lanza "Cannot use object of type stdClass as array". Como `count()` y
  `exists()` de las entidades se apoyan en `fetchColumn()`, **ambos quedaban rotos con ese
  fetch mode** — que es justamente el que recomienda el skeleton de proyecto
  (`PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ`). El bug aparecía contra MySQL y
  PostgreSQL reales; no se veía con el fetch mode por defecto (array).

  Ahora `fetchColumn()` normaliza la fila con `get_object_vars()` cuando es un objeto, así
  que funciona igual con filas array u objeto. Las filas array se comportan como antes.

### Añadido

- `tests/Unit/QueryBuilder/FetchColumnTest.php`: cubre `fetchColumn()` por nombre y por
  posición, con filas objeto y array, y los casos sin resultados / columna inexistente.