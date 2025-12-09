# Soft Delete Implementation

## Overzicht

De applicatie gebruikt nu **soft delete** in plaats van hard delete voor gebruikers. Dit betekent dat gebruikers niet permanent uit de database worden verwijderd, maar worden gemarkeerd als verwijderd.

## Wat is Soft Delete?

Bij soft delete wordt een `deleted_at` timestamp kolom gebruikt:
- **NULL** = gebruiker is actief
- **Datum/tijd** = gebruiker is verwijderd op dat moment

## Voordelen

✅ **Data behoud**: Gebruikersgegevens blijven bewaard voor auditing en rapportage  
✅ **Herstelbaar**: Verwijderde gebruikers kunnen worden hersteld  
✅ **Referentiële integriteit**: Foreign keys blijven geldig  
✅ **Audit trail**: Je kunt zien wanneer een gebruiker is verwijderd  
✅ **Historische data**: Rapporten kunnen nog steeds verwijderde gebruikers tonen indien nodig

## Database Wijzigingen

### Voor nieuwe installaties
De `deleted_at` kolom is al toegevoegd aan `database/init.sql`

### Voor bestaande databases
Voer de migratie uit:

```bash
# Via MySQL command line
mysql -u your_username -p gezondheidsmeter < database/migration_add_deleted_at.sql

# Of via Docker
docker exec -i gezondheidsmeter-db mysql -uroot -prootpass gezondheidsmeter < database/migration_add_deleted_at.sql
```

Of voer handmatig uit in phpMyAdmin:
```sql
ALTER TABLE `users` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `block_reason`;
```

## Code Wijzigingen

### User Model (`src/models/User.php`)

#### Nieuwe property
```php
public $deleted_at;
```

#### Nieuwe methods

**Soft Delete**
```php
$user = User::findByIdStatic($userId);
$result = $user->softDelete();
// Returns: ['success' => true/false, 'message' => '...']
```

**Restore**
```php
$user = User::findByIdStatic($userId);
$result = $user->restore();
// Returns: ['success' => true/false, 'message' => '...']
```

#### Aangepaste queries
Alle User model queries filteren nu automatisch soft-deleted users uit:
- `findByUsername()` - excludeert verwijderde users
- `findByUsernameStatic()` - excludeert verwijderde users
- `findById()` - excludeert verwijderde users
- `findByIdStatic()` - excludeert verwijderde users
- `getAll()` - excludeert verwijderde users
- `getAllUsers()` - excludeert verwijderde users
- `authenticate()` - excludeert verwijderde users
- `usernameExists()` - excludeert verwijderde users

### Admin Gebruikers Pagina (`admin/pages/gebruikers.php`)

De delete actie gebruikt nu `softDelete()` in plaats van `DELETE FROM`:

```php
// Oud (hard delete)
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Nieuw (soft delete)
$result = $targetUser->softDelete();
if ($result['success']) {
    // Success
}
```

## Gebruik

### Een gebruiker verwijderen (soft delete)
1. Ga naar Admin → Gebruikers
2. Klik op "Verwijderen" bij een gebruiker
3. Bevestig de actie
4. De gebruiker wordt gemarkeerd als verwijderd maar blijft in de database

### Een gebruiker herstellen
Momenteel alleen via code of database:

**Via code:**
```php
require_once 'src/models/User.php';
$user = User::findByIdStatic($userId);
if ($user) {
    $result = $user->restore();
}
```

**Via database:**
```sql
UPDATE users SET deleted_at = NULL WHERE id = ?;
```

### Verwijderde gebruikers bekijken
```sql
SELECT * FROM users WHERE deleted_at IS NOT NULL;
```

## Testing

1. **Test soft delete:**
   - Verwijder een gebruiker via de admin interface
   - Controleer in de database: `SELECT * FROM users WHERE id = X;`
   - De gebruiker moet nog bestaan met een `deleted_at` waarde

2. **Test dat verwijderde users niet verschijnen:**
   - Verwijderde users verschijnen niet in de gebruikerslijst
   - Verwijderde users kunnen niet inloggen
   - Verwijderde users worden niet gevonden door `User::findByIdStatic()`

3. **Test restore:**
   - Herstel een gebruiker via SQL of code
   - Controleer dat de gebruiker weer verschijnt in de lijst
   - Controleer dat de gebruiker weer kan inloggen

## Belangrijke Opmerkingen

⚠️ **Admins kunnen zichzelf niet verwijderen** - Dit is een veiligheidsmaatregel

⚠️ **Alle deletes worden gelogd** - De `admin_actions` tabel houdt bij wie wanneer welke gebruiker heeft verwijderd

⚠️ **Foreign keys blijven werken** - Omdat gebruikers niet echt worden verwijderd, blijven relaties met andere tabellen intact

## Toekomstige Uitbreidingen

Mogelijke verbeteringen:
- Admin interface om verwijderde gebruikers te bekijken
- Restore functionaliteit in de admin interface  
- Automatische cleanup van oude soft-deleted records (bijv. na 90 dagen)
- Bulk restore/permanent delete functionaliteit
