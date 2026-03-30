---
name: mydb-query-pattern
description: Guides correct usage of the MyDb query API: `$this->db->query()`, `num_rows()`, `next_record(MYSQL_ASSOC)`, `$this->db->Record`, and `real_escape()`. Use when user asks 'how do I query', 'how to insert', 'how to fetch rows', 'real_escape usage', or when writing any SQL in `src/`. Do NOT use for PDO-style prepared statements — this codebase uses the MyDb string-escape pattern, not `->prepare()`.
---
# mydb-query-pattern

## Critical

- **Never interpolate raw user input or entity values directly into SQL.** Every value MUST be wrapped in `$this->db->real_escape()`.
- **Never use PDO, `->prepare()`, or `->bindParam()`.** This codebase uses MyDb string-escape pattern exclusively.
- **Never call `next_record()` without first checking `num_rows() === 1`** (single row) or `num_rows() > 0` (multi-row).
- Return `null` (not `false`) when a record is not found.

## Instructions

1. **Single-row SELECT**
   Call `$this->db->query(...)`, check `num_rows() === 1`, then call `next_record(MYSQL_ASSOC)` and read `$this->db->Record`.
   ```php
   $this->db->query('SELECT * FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token).'"');
   if ($this->db->num_rows() === 1) {
       $this->db->next_record(MYSQL_ASSOC);
       $row = $this->db->Record;
   } else {
       return null;
   }
   ```
   Verify: `num_rows() === 1` guard is present before `next_record()`.

2. **Multi-row SELECT**
   Check `num_rows() > 0`, then loop with `while ($this->db->next_record(MYSQL_ASSOC))`.
   ```php
   $this->db->query('SELECT scope FROM oauth_access_token_scopes WHERE access_token = "'.$this->db->real_escape($token).'"');
   $scopes = [];
   if ($this->db->num_rows() > 0) {
       while ($this->db->next_record(MYSQL_ASSOC)) {
           $scopes[] = $this->db->Record['scope'];
       }
   }
   return $scopes;
   ```
   Verify: loop uses `while`, not `for`; `$this->db->Record` accessed inside the loop.

3. **INSERT**
   Build a quoted, escaped value list inline. No placeholders.
   ```php
   $this->db->query(
       'INSERT INTO oauth_access_tokens (access_token, expire_time, session_id) VALUES ('
       .'"'.$this->db->real_escape($token).'",'
       .'"'.$this->db->real_escape($expireTime).'",'
       .'"'.$this->db->real_escape($sessionId).'"'
       .')'
   );
   ```
   Verify: every column value is wrapped in `real_escape()`.

4. **DELETE**
   Same escape pattern; no return value needed.
   ```php
   $this->db->query('DELETE FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token->getId()).'"');
   ```
   Verify: the WHERE clause value is escaped.

5. **Hydrate and return entity**
   Use `(new FooEntity($this->server))->hydrate([...])` or individual setters. This step uses column values from `$this->db->Record` (Step 1 output).
   ```php
   return (new AccessTokenEntity($this->server))->hydrate([
       'id'          => $row['access_token'],
       'expire_time' => $row['expire_time'],
   ]);
   ```
   Verify: entity constructor receives `$this->server`.

## Examples

**User says:** "Add a method to look up a refresh token by its token string."

**Actions taken:**
1. SELECT with `real_escape` on the token string.
2. Guard with `num_rows() === 1`.
3. Call `next_record(MYSQL_ASSOC)`, read `$this->db->Record`.
4. Hydrate and return entity; return `null` on miss.

**Result:**
```php
public function get(string $token): ?RefreshTokenEntity
{
    $this->db->query('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "'.$this->db->real_escape($token).'"');
    if ($this->db->num_rows() === 1) {
        $this->db->next_record(MYSQL_ASSOC);
        $row = $this->db->Record;
        return (new RefreshTokenEntity($this->server))
            ->setId($row['refresh_token'])
            ->setExpireTime($row['expire_time']);
    }
    return null;
}
```

## Common Issues

- **`PHP Warning: next_record() called with 0 rows`** — you called `next_record()` without checking `num_rows()`. Add `if ($this->db->num_rows() === 1)` before the call.
- **SQL injection / unescaped value** — any variable interpolated directly (e.g., `WHERE id = $id`) must become `WHERE id = "'.$this->db->real_escape($id).'"`. There are no exceptions.
- **Method returns `false` instead of `null`** — the convention is `return null` on not-found. `false` breaks strict type checks in League OAuth2 interfaces.
- **Entity not hydrated from `$this->db->Record`** — access columns as `$this->db->Record['column_name']` inside the `if` block, after `next_record()` has been called.
- **Multi-row loop reads only first row** — if you used `if` instead of `while`, replace with `while ($this->db->next_record(MYSQL_ASSOC))`.