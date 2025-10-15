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

namespace PhobosFramework\Database\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Drivers\SQLiteDriver;
use PhobosFramework\Database\Entity\TableEntity;
use PhobosFramework\Database\Schema\SchemaRegistry;
use PDO;

/**
 * Clase de entidad para pruebas de integración que representa un usuario en la base de datos.
 *
 * Esta clase extiende TableEntity y está configurada para mapear a la tabla 'users'
 * en el esquema 'main' de la base de datos. Define la estructura y comportamiento
 * de los registros de usuario para pruebas.
 *
 * @property int|null $id     Identificador único del usuario (clave primaria autoincremental)
 * @property string $name   Nombre del usuario (requerido)
 * @property string $email  Correo electrónico del usuario (requerido)
 * @property string|null $status Estado del usuario (opcional)
 */
class TestUser extends TableEntity {
    protected static string $schema = 'main';
    protected static string $entity = 'users';
    protected static array $pk = ['id'];
    public ?int $id = null;
    public string $name;
    public string $email;
    public ?string $status = null;
}

/**
 * Clase de pruebas de integración para validar las operaciones CRUD de entidades.
 *
 * Esta clase prueba las funcionalidades básicas de creación, lectura, actualización y eliminación
 * de entidades en la base de datos, utilizando SQLite en memoria como motor de base de datos.
 * También verifica:
 * - Gestión de transacciones simples y anidadas
 * - Seguimiento de cambios en entidades
 * - Clonación de entidades
 * - Búsqueda y conteo de registros
 * - Operaciones en lote
 *
 * La clase utiliza una entidad de prueba (TestUser) que representa una tabla de usuarios
 * para ejecutar todas las pruebas de integración.
 */
class EntityCRUDTest extends TestCase {
    private ConnectionManager $manager;

    protected function setUp(): void {
        // Setup in-memory SQLite database
        $this->manager = ConnectionManager::getInstance();

        $this->manager->registerDriver('sqlite', new SQLiteDriver());
        $this->manager->addConnection('default', [
            'driver' => 'sqlite',
            'database' => ':memory:'
        ]);
        $this->manager->setDefaultConnection('default');

        // Create test table
        $connection = $this->manager->getConnection();
        $pdo = $connection->getPDO();

        $pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                status TEXT
            )
        ');

        // Register schema
        SchemaRegistry::getInstance()->register('main', 'main');
    }

    protected function tearDown(): void {
        // Reset singleton for next test
        $reflection = new \ReflectionClass(ConnectionManager::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $reflection = new \ReflectionClass(SchemaRegistry::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function test_create_entity(): void {
        $user = new TestUser();
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->status = 'active';

        $result = $user->save();

        $this->assertTrue($result);
        $this->assertNotNull($user->id);
        $this->assertFalse($user->isNew());
    }

    public function test_find_entity_by_primary_key(): void {
        // Create a user first
        $user = new TestUser();
        $user->name = 'Jane Doe';
        $user->email = 'jane@example.com';
        $user->save();

        $id = $user->id;

        // Find by PK
        $found = TestUser::findByPk($id);

        $this->assertNotNull($found);
        $this->assertEquals('Jane Doe', $found->name);
        $this->assertEquals('jane@example.com', $found->email);
    }

    public function test_update_entity(): void {
        // Create a user
        $user = new TestUser();
        $user->name = 'Bob Smith';
        $user->email = 'bob@example.com';
        $user->save();

        // Update the user
        $user->name = 'Robert Smith';
        $user->email = 'robert@example.com';
        $result = $user->save();

        $this->assertTrue($result);

        // Verify update
        $updated = TestUser::findByPk($user->id);
        $this->assertEquals('Robert Smith', $updated->name);
        $this->assertEquals('robert@example.com', $updated->email);
    }

    public function test_delete_entity(): void {
        // Create a user
        $user = new TestUser();
        $user->name = 'Alice Johnson';
        $user->email = 'alice@example.com';
        $user->save();

        $id = $user->id;

        // Delete the user
        $result = $user->remove();

        $this->assertTrue($result);

        // Verify deletion
        $deleted = TestUser::findByPk($id);
        $this->assertNull($deleted);
    }

    public function test_find_all_entities(): void {
        // Create multiple users
        $user1 = new TestUser();
        $user1->name = 'User 1';
        $user1->email = 'user1@example.com';
        $user1->status = 'active';
        $user1->save();

        $user2 = new TestUser();
        $user2->name = 'User 2';
        $user2->email = 'user2@example.com';
        $user2->status = 'inactive';
        $user2->save();

        $user3 = new TestUser();
        $user3->name = 'User 3';
        $user3->email = 'user3@example.com';
        $user3->status = 'active';
        $user3->save();

        // Find all active users
        $activeUsers = TestUser::find(['status = ?' => 'active']);

        $this->assertCount(2, $activeUsers);
        $this->assertInstanceOf(TestUser::class, $activeUsers[0]);
    }

    public function test_find_first_entity(): void {
        // Create users
        $user1 = new TestUser();
        $user1->name = 'First User';
        $user1->email = 'first@example.com';
        $user1->save();

        $user2 = new TestUser();
        $user2->name = 'Second User';
        $user2->email = 'second@example.com';
        $user2->save();

        // Find first user
        $first = TestUser::findFirst([], 'id ASC');

        $this->assertNotNull($first);
        $this->assertEquals('First User', $first->name);
    }

    public function test_count_entities(): void {
        // Create multiple users
        for ($i = 1; $i <= 5; $i++) {
            $user = new TestUser();
            $user->name = "User $i";
            $user->email = "user$i@example.com";
            $user->status = $i % 2 === 0 ? 'active' : 'inactive';
            $user->save();
        }

        // Count all users
        $total = TestUser::count();
        $this->assertEquals(5, $total);

        // Count active users
        $active = TestUser::count(['status = ?' => 'active']);
        $this->assertEquals(2, $active);
    }

    public function test_change_tracking(): void {
        // Create a user
        $user = new TestUser();
        $user->name = 'Track Me';
        $user->email = 'track@example.com';
        $user->save();

        // User should not be dirty after save
        $this->assertFalse($user->isDirty());

        // Modify the user
        $user->name = 'Changed Name';

        // Detect changes manually (required for public properties)
        $user->detectChanges();

        // User should now be dirty
        $this->assertTrue($user->isDirty());

        // Save should clear dirty state
        $user->save();
        $this->assertFalse($user->isDirty());
    }

    public function test_clone_entity(): void {
        // Create original user
        $original = new TestUser();
        $original->name = 'Original User';
        $original->email = 'original@example.com';
        $original->save();

        $originalId = $original->id;

        // Clone the user
        $clone = clone $original;
        $clone->email = 'clone@example.com';
        $clone->save();

        // Clone should have different ID
        $this->assertNotEquals($originalId, $clone->id);
        $this->assertTrue($clone->id > 0);

        // Both should exist in database
        $this->assertNotNull(TestUser::findByPk($originalId));
        $this->assertNotNull(TestUser::findByPk($clone->id));
    }

    public function test_transaction_commit(): void {
        $manager = $this->manager->getTransactionManager();

        $manager->begin();

        $user = new TestUser();
        $user->name = 'Transaction User';
        $user->email = 'transaction@example.com';
        $user->save();

        $manager->commit();

        // User should exist after commit
        $found = TestUser::findByPk($user->id);
        $this->assertNotNull($found);
    }

    public function test_transaction_rollback(): void {
        $manager = $this->manager->getTransactionManager();

        $manager->begin();

        $user = new TestUser();
        $user->name = 'Rollback User';
        $user->email = 'rollback@example.com';
        $user->save();

        $id = $user->id;

        $manager->rollback();

        // User should not exist after rollback
        $found = TestUser::findByPk($id);
        $this->assertNull($found);
    }

    public function test_nested_transactions(): void {
        $manager = $this->manager->getTransactionManager();

        $manager->begin();

        $user1 = new TestUser();
        $user1->name = 'User 1';
        $user1->email = 'user1@example.com';
        $user1->save();

        $sp = $manager->begin(); // Nested transaction

        $user2 = new TestUser();
        $user2->name = 'User 2';
        $user2->email = 'user2@example.com';
        $user2->save();

        $manager->rollback($sp); // Rollback nested only

        $manager->commit(); // Commit outer transaction

        // User 1 should exist
        $this->assertNotNull(TestUser::findByPk($user1->id));

        // User 2 should not exist (rolled back)
        $this->assertNull(TestUser::findByPk($user2->id));
    }
}
