# Migrating PRISM and PHP

This document describes the supported upgrade paths, the exact PHP changes at
each boundary, and what PRISM itself changes. It distinguishes intentional PRISM
API changes from PHP language/runtime changes that no PRISM release can hide.

## Version matrix

| PRISM | PHP target | PRISM API | Status |
|---|---|---|---|
| `0.6.x` | 5.6 | 0.6 packet/plugin/page API | Legacy; frozen |
| `0.7.x` | 5.6–7.1 | Same as 0.6 | PHP 7.1 bridge release |
| `1.0.x` | 8.5 | Deliberate breaking release | Not yet released |

`0.7.x` is the **PHP 7.1 bridge**. It keeps the old packet, plugin and page
APIs so existing mods do not need a redesign. It also keeps PHP 5.6 support, so
the upgrade can be done in either order:

```text
PRISM 0.6.x (PHP 5.6)
        |
        +--> PRISM 0.7.x on PHP 5.6
        |          |
        |          +--> PRISM 0.7.x on PHP 7.1
        |                     |
        +---------------------+--> PHP 8.5 + PRISM 1.0.x
```

The test suite is also run on PHP 8.5 to catch forward-compatibility
regressions. That is **not** a claim that the `0.7.x` daemon is production-ready
on 8.5; see [Known 0.7.x gaps](#known-07x-gaps).

PHP 5.6 and 7.1 are end-of-life. Use them only while migrating. A public
internet-facing server should be on 8.5 with `1.0.x` once it is available.

## Why 7.1 is the bridge

PHP 7.1 is the last release where all of the old PRISM-era names and syntax
still work:

- `class Object` is legal through 7.1. PHP 7.2 reserves `object`, so the same
  class name is a fatal error from 7.2 onward.
- `$str{0}` is still legal in 7.1. It is deprecated in 7.4 and removed in 8.0.
- `each()` and `create_function()` still exist in 7.1. They are removed in 8.0.
- The `implode($array, $separator)` / `join($array, $separator)` argument order
  still works in 7.1. It is removed in 8.0.
- `set_error_handler()` still supplies the fifth `$errcontext` argument in 7.1.
  It is removed in 8.0.

That makes 7.1 the last release where a mod using those old constructs can run
without source changes. It is a compatibility stop, not a modern target.

## PHP 5.6 -> 7.1

### PHP changes that affect mods

These are language/runtime changes, not PRISM API changes.

| Change | First affected | Action |
|---|---|---|
| `mysql_*` extension removed | 7.0 | Port to `mysqli` or PDO. PRISM itself has no live MySQL dependency. |
| `ereg()` / `eregi()` removed | 7.0 | Use `preg_match()` with delimiters. |
| `split()` / `spliti()` removed | 7.0 | Use `preg_split()` or `explode()`. |
| `preg_replace()` `/e` modifier removed | 7.0 | Use `preg_replace_callback()`. |
| `mcrypt_*` deprecated, then removed | 7.1 / 7.2 | Use OpenSSL or Sodium. It still works on 7.1 but does not survive 7.2. |
| Engine failures become `Error`/`TypeError` | 7.0 | Do not assume every failure is an `Exception`. On 7.1+, catch `Throwable` where possible; code that must also parse on 5.6 can only catch `Exception`. |
| `set_error_handler` still has 5 args | through 7.1 | A mod handler declaring a required fifth argument still works on 7.1. It does **not** work on 8.0+. |

### PRISM changes in 0.7

`0.7.0` makes no intentional **name or signature** changes to the packet,
plugin or page APIs. It does:

- Keep `PTH` working through `class_alias('Path', 'PTH')`.
- Keep `Object` working on PHP < 7.2 through an alias to `SMXObject`.
- Keep the `Object` class name in `prism_smx.php` only as an alias; the
  implementation is `SMXObject`.
- Expose both the documented `$_RESPONSE`, `$_GET`, `$_POST`, `$_COOKIE`,
  `$_FILES` page names and the historical `$RESPONSE`, `$GET`, `$POST`,
  `$COOKIE` names.
- Remove PHP 8.5 deprecations from core code paths where possible.

### Behavioral fixes that are visible to mods

`0.7.0` fixes several packet codec bugs. These are bug fixes, not intentional
API breaks, but a mod that consumed the broken values will notice the change:

- `IS_REO->PLID` is now zero-based after `unpack()`. The old code used keys
  starting at 1, which made `pack()` shift the list by one.
- `IS_CPP->Pos` is now an array after `unpack()`. `Time` and `Flags` are read as
  16-bit words, matching the LFS layout.
- `IR_HOS->Info` now contains decoded `HInfo` objects; previously it was not
  decoded at all.
- `OutSimPack->AngVel`, `Accel`, `Vel` and `Pos` are arrays after `unpack()`,
  rather than only `AngVel1..3`-style properties.
- Packets with private/protected spare fields now pack in the declared field
  order. A few 0.6 packets were malformed because the base codec could not see
  child-private fields.

The 0.7 packet fixture is therefore the new byte baseline. For packets that were
already encoded correctly, the bytes are unchanged. For the malformed cases,
0.7 deliberately changes the bytes to the correct layout; do not compare those
against a 0.6 capture.

### Upgrade checklist

1. Back up `configs/`, `admins.ini`, `data/`, and every plugin.
2. Run every plugin through `php -l` under 7.1.
3. Search mods for the removed APIs above.
4. Run PRISM 0.7 on 5.6 first; confirm plugins still work.
5. Switch the CLI to 7.1; run `php tests/run.php` and a staging server.
6. Only then decide whether to stay on 7.1 or move to 8.5 with 1.0.

Useful audit commands:

```sh
grep -RInE '\b(mysql_|ereg|eregi|split|each|create_function|mcrypt_)' plugins
grep -RInE '\$[A-Za-z_][A-Za-z0-9_]*\{[^}]*\}' plugins
grep -RInE '\bimplode\s*\(\s*\$[A-Za-z_]' plugins
grep -RIn 'class Object' plugins
```

## PHP 7.1 -> 8.5

This is the large jump. Several constructs that were merely deprecated in the
7.x line are removed in 8.0, and 8.2+ adds deprecations for dynamic properties
and old interpolation.

### PHP 7.2

- `object` becomes a reserved type name. `class Object` is a fatal error.
  Use `SMXObject` on PRISM 0.7+, or rename the mod class.
- `mcrypt_*` is removed. Use OpenSSL/Sodium.
- `each()` and `create_function()` are deprecated. They still work through 7.4
  but are removed in 8.0.

### PHP 8.0

| Removed / changed | Replacement |
|---|---|
| `each()` | `foreach` |
| `create_function()` | Anonymous function / closure |
| `__autoload()` | `spl_autoload_register()` |
| `track_errors` / `$php_errormsg` | `error_get_last()` |
| `$str{0}` | `$str[0]` |
| `implode($array, $separator)` / `join($array, $separator)` | `implode($separator, $array)` |
| String `assert()` evaluation | Explicit checks or `assert($bool)` |
| `@` silencing fatal errors | `@` no longer hides `E_ERROR`, `E_PARSE`, `E_USER_ERROR`, etc. |
| `count()` on a non-countable value | Guard with `is_array()` / `Countable` |
| Undefined array key silently ignored | Guard with `isset()` / `array_key_exists()` |
| Loose number/string comparisons | Use `===`, `hash_equals()`, explicit casts |
| Nested ternaries without parentheses | Add explicit parentheses |

The `set_error_handler()` fifth argument (`$errcontext`) is also removed in
8.0. Any handler declaring five required parameters must make the fifth
optional or remove it.

### PHP 8.1–8.4

| Version | Change | Replacement |
|---|---|---|
| 8.1 | Passing `null` to non-nullable internal function parameters is deprecated | Default to `''`, `0`, or explicit nullable values |
| 8.1 | `FILTER_SANITIZE_STRING` deprecated | `htmlspecialchars()` / explicit filtering |
| 8.1 | `strftime()` / `gmstrftime()` deprecated | `date()` or `IntlDateFormatter` |
| 8.1 | `imagefilledpolygon()` `num_points` deprecated | 3-argument form |
| 8.2 | Dynamic properties deprecated | Declare properties or `#[\AllowDynamicProperties]` |
| 8.2 | `"${var}"` interpolation deprecated | `"{$var}"` |
| 8.2 | `utf8_encode()` / `utf8_decode()` deprecated | `mb_convert_encoding()` / `iconv()` |
| 8.3 | `get_class()` without an argument deprecated | Pass `$this` or use `::class` |
| 8.4 | `E_STRICT` deprecated | `E_ALL` |
| 8.4 | `trigger_error(..., E_USER_ERROR)` deprecated | Throw an exception or `exit()` |
| 8.4 | `str_getcsv()` / `fgetcsv()` default `$escape` deprecated | Pass the escape argument explicitly |
| 8.4 | Implicitly nullable parameters deprecated | `?Type $param = null` |

### PHP 8.5

| Change | Replacement |
|---|---|
| `(boolean)`, `(integer)`, `(double)`, `(binary)` casts deprecated | `(bool)`, `(int)`, `(float)`, `(string)` |
| `null` used as an array offset deprecated | Normalize to `''` or guard before indexing |
| `case ...;` deprecated | `case ...:` |
| `chr()` values outside 0–255, multi-byte `ord()` deprecated | Pass one byte / a single-character string |
| `imagedestroy()` deprecated | Let the `GdImage` object be released |
| `report_memleaks` deprecated | Remove the INI directive |
| Constant redeclaration deprecated | Define each constant once |
| `__sleep()` / `__wakeup()` soft-deprecated | `__serialize()` / `__unserialize()` |

## PRISM 0.7.x on 8.5: what is and is not done

The 0.7 codebase has been tested on 5.6, 7.1 and 8.5 at the class/packet/plugin
level. The following core fixes are present:

- Packet codec fixes for protected spare fields, repeated fields, null fields
  and reconstruction of array fields after `unpack()`.
- `IS_CPP` PACK/UNPACK widths and `Pos` normalization.
- `IS_REO` zero-based `PLID` ordering.
- `IR_HOS`/`HInfo` decoding and OutSim vector arrays.
- `PTH` version/revision byte comparison and `PTH`/`Path` aliasing.
- `SMXObject` instead of the reserved `Object` class name.
- `$str{0}`, legacy `join()` order and `(boolean)` removed from core files.
- Page variable aliases for `$_RESPONSE`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`.
- `#[\AllowDynamicProperties]` on extensible base classes.

However, 0.7.x is not yet a fully validated 8.5 **daemon**. These are the known
gaps that 1.0 must close:

1. `PHPInSimMod::_errorHandler()` still declares the removed fifth
   `$errcontext` parameter. On 8.0+, the first warning/notice invokes the handler
   with four arguments and raises `ArgumentCountError`.
2. `E_STRICT` is still passed to `set_error_handler()` and appears in the
   handler switch. It is deprecated in 8.4.
3. `trigger_error(..., E_USER_ERROR)` remains in the PTH/SMX parsers. It is
   deprecated in 8.4; malformed data should throw instead.
4. Plugin command parsing still calls `str_getcsv()` without the explicit
   escape argument. This is deprecated in 8.4.
5. `php.ini` is still a PHP 5-era file (`safe_mode`, `register_globals`,
   `track_errors`, `asp_tags`, `allow_call_time_pass_reference`, old Xdebug DLL,
   8 MB memory limit).
6. Timer/select-timeout calculation and null-resource guards in the host,
   HTTP and telnet loops still need the 8.x hardening pass.
7. The full daemon has not yet been started against a fake InSim host on 8.5;
   the current 8.5 proof is the class-level test matrix.

Until those are closed, use 0.7.x for the 5.6/7.1 bridge, and treat 8.5 as a
porting/test target rather than a production runtime.

## PRISM 1.0.x: intentional breaks

`1.0.x` is the first release allowed to break compatibility. The minimum
confirmed break list is:

1. **PHP 8.5 is required.** PHP 5.6 and every 7.x release are unsupported.
2. **The `PTH` alias is removed.** Use `Path`.
3. **The `Object` alias is gone.** It only existed for PHP < 7.2 and cannot
   exist on 8.5 anyway. Use `SMXObject`.
4. **The duplicate `PlayerHandler` file is removed.** The real handler remains
   in `prism_statehandler.php`.
5. **Dead modules are removed or explicitly unsupported.** This includes the
   unused database/cron/file/layout/time paths and the legacy player shim.
6. **Core compatibility shims are removed.** The daemon requires the 8.5-native
   error handler, exceptions instead of fatal `trigger_error`, explicit CSV
   escapes, a modern `php.ini`, null-safe socket loops and a tested timer path.

Separately, 1.0 is planned to move admin credential storage to
`password_hash()` / `password_verify()`. Existing hashes will be migrated on
successful login; the old SHA-1/MD5 format will not be accepted indefinitely.
That is a security migration, not a reason to delay the runtime port.

What 1.0 does **not** break, by policy:

- `IS_*()` / `IR_*()` packet factory functions remain.
- `Struct` chainable field setters and `pack()` / `unpack()` remain.
- `Plugins` / `registerPacket()` / `registerCommand()` / `sendPacket()` remain.
- Existing packet type names and wire formats remain.
- `$PRISM` remains available.
- Existing config file formats remain.

Namespaces, PSR-4, typed public APIs, dependency injection replacing `$PRISM`,
and a fully new packet API belong in a later major release (`2.0`), not 1.0.
That keeps the 0.7 bridge useful instead of breaking every mod twice.

## Test and rollback

Before upgrading a staging server:

```sh
php -l <every-plugin>.php
php tests/run.php
php tests/tools/dump_packets.php > /tmp/prism-packets.dump
diff -u tests/fixtures/packets.dump /tmp/prism-packets.dump
```

The packet dump must be empty-diff. On 0.7.x, the fixture is captured on PHP
5.6 and replayed on 7.1/8.5; any packet-byte difference is a regression.

Rollback is configuration-safe: 0.7 does not change the on-disk packet or
plugin formats. Keep the 0.6/0.7 config and binary until the 1.0 admin-hash
migration is understood. Do not run 1.0 against an `admins.ini` you cannot
restore.
