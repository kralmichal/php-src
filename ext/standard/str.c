/*
   +----------------------------------------------------------------------+
   | Copyright © The PHP Group and Contributors.                          |
   +----------------------------------------------------------------------+
   | This source file is subject to the Modified BSD License that is      |
   | bundled with this package in the file LICENSE, and is available      |
   | through the World Wide Web at <https://www.php.net/license/>.        |
   |                                                                      |
   | SPDX-License-Identifier: BSD-3-Clause                                |
   +----------------------------------------------------------------------+
*/

/* Str — internal backing class for scalar string methods.
 *
 * Each method is static and takes the subject string as its first argument,
 * returning a new value (immutable). This class is the dispatch target for
 * the scalar-objects compiler feature; userland should not instantiate it.
 */

#include "php.h"
#include "php_string.h"
#include "str_arginfo.h"

zend_class_entry *str_ce;

/* {{{ Str::trim(string $string, string $characters = " \n\r\t\v\0"): string */
PHP_METHOD(Str, trim)
{
	zend_string *str;
	zend_string *what = NULL;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_STR(str)
		Z_PARAM_OPTIONAL
		Z_PARAM_STR(what)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_STR(php_trim(str,
		what ? ZSTR_VAL(what) : NULL,
		what ? ZSTR_LEN(what) : 0,
		3 /* both ends */));
}
/* }}} */

/* {{{ Str::upper(string $string): string */
PHP_METHOD(Str, upper)
{
	zend_string *str;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(str)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_STR(zend_string_toupper(str));
}
/* }}} */

/* {{{ Str::lower(string $string): string */
PHP_METHOD(Str, lower)
{
	zend_string *str;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(str)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_STR(zend_string_tolower(str));
}
/* }}} */

/* {{{ Str::length(string $string): int */
PHP_METHOD(Str, length)
{
	zend_string *str;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(str)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_LONG((zend_long) ZSTR_LEN(str));
}
/* }}} */

PHP_MINIT_FUNCTION(str) /* {{{ */
{
	/* Register the backing class via the generated registrar (so its method table, flags and
	 * arginfo remain the single generated source of truth — the compiler relies on the return
	 * types for chaining), then make it internal-only by renaming it to a userland-unrepresentable
	 * name whose first byte is NUL (ZEND_STR_SCALAR_METHODS_CLASS_NAME). The class machinery for
	 * the `<string>->method()` desugar stays intact, but there is no user-visible global `Str`:
	 *   - userland `class Str {}` registers under key "str" and no longer collides;
	 *   - class_exists('Str') and Reflection-by-"Str" lowercase to "str" and miss the NUL-keyed entry;
	 *   - get_declared_classes() / ReflectionExtension already skip class-table keys whose first byte
	 *     is NUL (the anonymous-class convention).
	 * The desugar references this same internal name and is compiled as an ordinary static call,
	 * resolved at runtime through the call cache slot by name — never a baked CE pointer — so it
	 * persists and relocates correctly under opcache SHM and file_cache. */
	str_ce = register_class_Str();

	zend_string *internal_name = zend_string_init_interned(
		ZEND_STR_SCALAR_METHODS_CLASS_NAME, ZEND_STR_SCALAR_METHODS_CLASS_NAME_LEN, 1);
	zend_string *internal_key = zend_string_tolower_ex(internal_name, /* persistent */ true);
	internal_key = zend_new_interned_string(internal_key);

	/* Move the class-table entry from "str" to the NUL-prefixed key without freeing the class:
	 * the entry is re-inserted immediately below. ZEND_CLASS_DTOR would otherwise destroy the CE,
	 * so the destructor is suppressed for this single delete (a localized, established pattern). */
	zend_string *old_key = zend_string_tolower(str_ce->name);
	dtor_func_t orig_dtor = CG(class_table)->pDestructor;
	CG(class_table)->pDestructor = NULL;
	zend_hash_del(CG(class_table), old_key);
	CG(class_table)->pDestructor = orig_dtor;
	zend_string_release(old_key);

	zend_string_release_ex(str_ce->name, /* persistent */ true);
	str_ce->name = internal_name;
	zend_hash_add_ptr(CG(class_table), internal_key, str_ce);
	/* Refresh the inline CE cache so name-based lookups of the new name hit the fast path. */
	zend_alloc_ce_cache(str_ce->name);
	zend_string_release_ex(internal_key, /* persistent */ true);

	return SUCCESS;
}
/* }}} */
