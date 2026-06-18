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

/* Int — internal backing class for scalar int methods.
 *
 * Each method is static and takes the subject int as its first argument,
 * returning a new value. This class is the dispatch target for the
 * scalar-objects compiler feature; userland should not instantiate it.
 *
 * The curated method set reuses PHP's existing math primitives:
 *   - Int::pow(int, int): int|float — pow() may overflow to float (and
 *     pow(2, -1) === 0.5), so the result is int|float and is therefore a
 *     terminal call (not a chainable guaranteed-int receiver).
 *   - Int::abs(int): int — abs() returns int for an int input (except the
 *     ZEND_LONG_MIN edge case, which already overflows to float in the core
 *     abs(); the declared : int return is the documented happy path used for
 *     chaining decisions).
 */

#include "php.h"
#include "php_math.h"
#include "int_arginfo.h"

zend_class_entry *int_ce;

/* {{{ Int::pow(int $num, int $exponent): int|float */
PHP_METHOD(Int, pow)
{
	zend_long num, exponent;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_LONG(num)
		Z_PARAM_LONG(exponent)
	ZEND_PARSE_PARAMETERS_END();

	zval zbase, zexp;
	ZVAL_LONG(&zbase, num);
	ZVAL_LONG(&zexp, exponent);
	pow_function(return_value, &zbase, &zexp);
}
/* }}} */

/* {{{ Int::abs(int $num): int */
PHP_METHOD(Int, abs)
{
	zend_long num;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_LONG(num)
	ZEND_PARSE_PARAMETERS_END();

	if (UNEXPECTED(num == ZEND_LONG_MIN)) {
		RETURN_DOUBLE(-(double)ZEND_LONG_MIN);
	}
	RETURN_LONG(num < 0 ? -num : num);
}
/* }}} */

PHP_MINIT_FUNCTION(int) /* {{{ */
{
	/* Register the backing class via the generated registrar (so its method table, flags and
	 * arginfo remain the single generated source of truth — the compiler relies on the return
	 * types for chaining), then make it internal-only by renaming it to a userland-unrepresentable
	 * name whose first byte is NUL (ZEND_STR_SCALAR_METHODS_INT_CLASS_NAME). The class machinery
	 * for the `<int>->method()` desugar stays intact, but there is no user-visible global `Int`:
	 *   - userland `class Int {}` registers under key "int" and no longer collides;
	 *   - class_exists('Int') and Reflection-by-"Int" lowercase to "int" and miss the NUL-keyed entry;
	 *   - get_declared_classes() / ReflectionExtension already skip class-table keys whose first byte
	 *     is NUL (the anonymous-class convention).
	 * The desugar references this same internal name and is compiled as an ordinary static call,
	 * resolved at runtime through the call cache slot by name — never a baked CE pointer — so it
	 * persists and relocates correctly under opcache SHM and file_cache.
	 *
	 * This mirrors the Str backing class exactly (see ext/standard/str.c). */
	int_ce = register_class_Int();

	zend_string *internal_name = zend_string_init_interned(
		ZEND_STR_SCALAR_METHODS_INT_CLASS_NAME, ZEND_STR_SCALAR_METHODS_INT_CLASS_NAME_LEN, 1);
	zend_string *internal_key = zend_string_tolower_ex(internal_name, /* persistent */ true);
	internal_key = zend_new_interned_string(internal_key);

	/* Move the class-table entry from "int" to the NUL-prefixed key without freeing the class:
	 * the entry is re-inserted immediately below. ZEND_CLASS_DTOR would otherwise destroy the CE,
	 * so the destructor is suppressed for this single delete (a localized, established pattern). */
	zend_string *old_key = zend_string_tolower(int_ce->name);
	dtor_func_t orig_dtor = CG(class_table)->pDestructor;
	CG(class_table)->pDestructor = NULL;
	zend_hash_del(CG(class_table), old_key);
	CG(class_table)->pDestructor = orig_dtor;
	zend_string_release(old_key);

	zend_string_release_ex(int_ce->name, /* persistent */ true);
	int_ce->name = internal_name;
	zend_hash_add_ptr(CG(class_table), internal_key, int_ce);
	/* Refresh the inline CE cache so name-based lookups of the new name hit the fast path. */
	zend_alloc_ce_cache(int_ce->name);
	zend_string_release_ex(internal_key, /* persistent */ true);

	return SUCCESS;
}
/* }}} */
