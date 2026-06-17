/*
   +----------------------------------------------------------------------+
   | Zend Engine                                                          |
   +----------------------------------------------------------------------+
   | Copyright © Zend Technologies Ltd., a subsidiary company of          |
   |     Perforce Software, Inc., and Contributors.                       |
   +----------------------------------------------------------------------+
   | This source file is subject to the Modified BSD License that is      |
   | bundled with this package in the file LICENSE, and is available      |
   | through the World Wide Web at <https://www.php.net/license/>.        |
   |                                                                      |
   | SPDX-License-Identifier: BSD-3-Clause                                |
   +----------------------------------------------------------------------+
   | Authors: Andi Gutmans <andi@php.net>                                 |
   |          Zeev Suraski <zeev@php.net>                                 |
   |          Dmitry Stogov <dmitry@php.net>                              |
   +----------------------------------------------------------------------+
*/

#ifndef ZEND_EXECUTE_H
#define ZEND_EXECUTE_H

#include "zend_compile.h"
#include "zend_hash.h"
#include "zend_operators.h"
#include "zend_variables.h"
#include "zend_constants.h"

#include <stdint.h>

BEGIN_EXTERN_C()
struct _zend_fcall_info;
ZEND_API extern void (*zend_execute_ex)(zend_execute_data *execute_data);
ZEND_API extern void (*zend_execute_internal)(zend_execute_data *execute_data, zval *return_value);

/* The lc_name may be stack allocated! */
ZEND_API extern zend_class_entry *(*zend_autoload)(zend_string *name, zend_string *lc_name);

void init_executor(void);
void shutdown_executor(void);
void shutdown_destructors(void);
ZEND_API void zend_shutdown_executor_values(bool fast_shutdown);

ZEND_API void zend_init_execute_data(zend_execute_data *execute_data, zend_op_array *op_array, zval *return_value);
ZEND_API void zend_init_func_execute_data(zend_execute_data *execute_data, zend_op_array *op_array, zval *return_value);
ZEND_API void zend_init_code_execute_data(zend_execute_data *execute_data, zend_op_array *op_array, zval *return_value);
ZEND_API void zend_execute(zend_op_array *op_array, zval *return_value);
ZEND_API void execute_ex(zend_execute_data *execute_data);
ZEND_API void execute_internal(zend_execute_data *execute_data, zval *return_value);
ZEND_API bool zend_is_valid_class_name(const zend_string *name);
ZEND_API zend_class_entry *zend_lookup_class(zend_string *name);
ZEND_API zend_class_entry *zend_lookup_class_ex(zend_string *name, zend_string *lcname, uint32_t flags);
ZEND_API zend_class_entry *zend_get_called_scope(const zend_execute_data *ex);
ZEND_API zend_object *zend_get_this_object(const zend_execute_data *ex);
ZEND_API zend_result zend_eval_string(const char *str, zval *retval_ptr, const char *string_name);
ZEND_API zend_result zend_eval_stringl(const char *str, size_t str_len, zval *retval_ptr, const char *string_name);
ZEND_API zend_result zend_eval_string_ex(const char *str, zval *retval_ptr, const char *string_name, bool handle_exceptions);
ZEND_API zend_result zend_eval_stringl_ex(const char *str, size_t str_len, zval *retval_ptr, const char *string_name, bool handle_exceptions);

/* export zend_pass_function to allow comparisons against it */
extern ZEND_API const zend_internal_function zend_pass_function;

ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_missing_arg_error(const zend_execute_data *execute_data);
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_deprecated_function(const zend_function *fbc);
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_nodiscard_function(const zend_function *fbc);
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_deprecated_class_constant(const zend_class_constant *c, const zend_string *constant_name);
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_deprecated_constant(const zend_constant *c, const zend_string *constant_name);
ZEND_API ZEND_COLD void zend_use_of_deprecated_trait(zend_class_entry *trait, const zend_string *used_by);
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_false_to_array_deprecated(void);
ZEND_COLD void ZEND_FASTCALL zend_param_must_be_ref(const zend_function *func, uint32_t arg_num);
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_use_resource_as_offset(const zval *dim);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_call_stack_size_error(void);

ZEND_API bool ZEND_FASTCALL zend_verify_ref_assignable_zval(zend_reference *ref, zval *zv, bool strict);

typedef enum {
	ZEND_VERIFY_PROP_ASSIGNABLE_BY_REF_CONTEXT_ASSIGNMENT,
	ZEND_VERIFY_PROP_ASSIGNABLE_BY_REF_CONTEXT_MAGIC_GET,
} zend_verify_prop_assignable_by_ref_context;
ZEND_API bool ZEND_FASTCALL zend_verify_prop_assignable_by_ref_ex(const zend_property_info *prop_info, zval *orig_val, bool strict, zend_verify_prop_assignable_by_ref_context context);
ZEND_API bool ZEND_FASTCALL zend_verify_prop_assignable_by_ref(const zend_property_info *prop_info, zval *orig_val, bool strict);

ZEND_API zend_never_inline ZEND_COLD void zend_throw_ref_type_error_zval(const zend_property_info *prop, const zval *zv);
ZEND_API zend_never_inline ZEND_COLD void zend_throw_ref_type_error_type(const zend_property_info *prop1, const zend_property_info *prop2, const zval *zv);
ZEND_API ZEND_COLD zval* ZEND_FASTCALL zend_undefined_offset_write(HashTable *ht, zend_long lval);
ZEND_API ZEND_COLD zval* ZEND_FASTCALL zend_undefined_index_write(HashTable *ht, zend_string *offset);
ZEND_API zend_never_inline ZEND_COLD void zend_wrong_string_offset_error(void);

ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_readonly_property_modification_error(const zend_property_info *info);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_readonly_property_modification_error_ex(const char *class_name, const char *prop_name);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_readonly_property_indirect_modification_error(const zend_property_info *info);

ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_invalid_class_constant_type_error(uint8_t type);

ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_object_released_while_assigning_to_property_error(const zend_property_info *info);

ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_cannot_add_element(void);

ZEND_API bool ZEND_FASTCALL zend_asymmetric_property_has_set_access(const zend_property_info *prop_info);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_asymmetric_visibility_property_modification_error(const zend_property_info *prop_info, const char *operation);

ZEND_API bool zend_verify_scalar_type_hint(uint32_t type_mask, zval *arg, bool strict, bool is_internal_arg);
ZEND_API zend_never_inline ZEND_COLD void zend_verify_arg_error(
		const zend_function *zf, const zend_arg_info *arg_info, uint32_t arg_num, const zval *value);
ZEND_API zend_never_inline ZEND_COLD void zend_verify_return_error(
		const zend_function *zf, const zval *value);
ZEND_API zend_never_inline ZEND_COLD void zend_verify_never_error(
		const zend_function *zf);
ZEND_API bool zend_verify_ref_array_assignable(zend_reference *ref);
ZEND_API bool zend_check_user_type_slow(
		const zend_type *type, zval *arg, const zend_reference *ref, bool is_return_type);

#if ZEND_DEBUG
ZEND_API bool zend_internal_call_should_throw(const zend_function *fbc, zend_execute_data *call);
ZEND_API zend_never_inline ZEND_COLD void zend_internal_call_arginfo_violation(const zend_function *fbc);
ZEND_API bool zend_verify_internal_return_type(const zend_function *zf, zval *ret);
#endif

#define ZEND_REF_TYPE_SOURCES(ref) \
	(ref)->sources

#define ZEND_REF_HAS_TYPE_SOURCES(ref) \
	(ZEND_REF_TYPE_SOURCES(ref).ptr != NULL)

#define ZEND_REF_FIRST_SOURCE(ref) \
	(ZEND_PROPERTY_INFO_SOURCE_IS_LIST((ref)->sources.list) \
		? ZEND_PROPERTY_INFO_SOURCE_TO_LIST((ref)->sources.list)->ptr[0] \
		: (ref)->sources.ptr)


ZEND_API void ZEND_FASTCALL zend_ref_add_type_source(zend_property_info_source_list *source_list, zend_property_info *prop);
ZEND_API void ZEND_FASTCALL zend_ref_del_type_source(zend_property_info_source_list *source_list, const zend_property_info *prop);

ZEND_API zval* zend_assign_to_typed_ref(zval *variable_ptr, zval *value, uint8_t value_type, bool strict);
ZEND_API zval* zend_assign_to_typed_ref_ex(zval *variable_ptr, zval *value, uint8_t value_type, bool strict, zend_refcounted **garbage_ptr);

static zend_always_inline void zend_copy_to_variable(zval *variable_ptr, const zval *value, uint8_t value_type)
{
	zend_refcounted *ref = NULL;

	if (ZEND_CONST_COND(value_type & (IS_VAR|IS_CV), 1) && Z_ISREF_P(value)) {
		ref = Z_COUNTED_P(value);
		value = Z_REFVAL_P(value);
	}

	ZVAL_COPY_VALUE(variable_ptr, value);
	if (ZEND_CONST_COND(value_type  == IS_CONST, 0)) {
		if (UNEXPECTED(Z_OPT_REFCOUNTED_P(variable_ptr))) {
			Z_ADDREF_P(variable_ptr);
		}
	} else if (value_type & (IS_CONST|IS_CV)) {
		if (Z_OPT_REFCOUNTED_P(variable_ptr)) {
			Z_ADDREF_P(variable_ptr);
		}
	} else if (ZEND_CONST_COND(value_type == IS_VAR, 1) && UNEXPECTED(ref)) {
		if (UNEXPECTED(GC_DELREF(ref) == 0)) {
			efree_size(ref, sizeof(zend_reference));
		} else if (Z_OPT_REFCOUNTED_P(variable_ptr)) {
			Z_ADDREF_P(variable_ptr);
		}
	}
}

static zend_always_inline zval* zend_assign_to_variable(zval *variable_ptr, zval *value, uint8_t value_type, bool strict)
{
	do {
		if (UNEXPECTED(Z_REFCOUNTED_P(variable_ptr))) {
			zend_refcounted *garbage;

			if (Z_ISREF_P(variable_ptr)) {
				if (UNEXPECTED(ZEND_REF_HAS_TYPE_SOURCES(Z_REF_P(variable_ptr)))) {
					return zend_assign_to_typed_ref(variable_ptr, value, value_type, strict);
				}

				variable_ptr = Z_REFVAL_P(variable_ptr);
				if (EXPECTED(!Z_REFCOUNTED_P(variable_ptr))) {
					break;
				}
			}
			garbage = Z_COUNTED_P(variable_ptr);
			zend_copy_to_variable(variable_ptr, value, value_type);
			GC_DTOR_NO_REF(garbage);
			return variable_ptr;
		}
	} while (0);

	zend_copy_to_variable(variable_ptr, value, value_type);
	return variable_ptr;
}

static zend_always_inline zval* zend_assign_to_variable_ex(zval *variable_ptr, zval *value, zend_uchar value_type, bool strict, zend_refcounted **garbage_ptr)
{
	do {
		if (UNEXPECTED(Z_REFCOUNTED_P(variable_ptr))) {
			if (Z_ISREF_P(variable_ptr)) {
				if (UNEXPECTED(ZEND_REF_HAS_TYPE_SOURCES(Z_REF_P(variable_ptr)))) {
					return zend_assign_to_typed_ref_ex(variable_ptr, value, value_type, strict, garbage_ptr);
				}

				variable_ptr = Z_REFVAL_P(variable_ptr);
				if (EXPECTED(!Z_REFCOUNTED_P(variable_ptr))) {
					break;
				}
			}
			*garbage_ptr = Z_COUNTED_P(variable_ptr);
		}
	} while (0);

	zend_copy_to_variable(variable_ptr, value, value_type);
	return variable_ptr;
}

static zend_always_inline void zend_safe_assign_to_variable_noref(zval *variable_ptr, const zval *value) {
	if (Z_REFCOUNTED_P(variable_ptr)) {
		ZEND_ASSERT(Z_TYPE_P(variable_ptr) != IS_REFERENCE);
		zend_refcounted *ref = Z_COUNTED_P(variable_ptr);
		ZVAL_COPY_VALUE(variable_ptr, value);
		GC_DTOR_NO_REF(ref);
	} else {
		ZVAL_COPY_VALUE(variable_ptr, value);
	}
}

static zend_always_inline void zend_cast_zval_to_object(zval *result, zval *expr, uint8_t op1_type) {
	HashTable *ht;

	ZVAL_OBJ(result, zend_objects_new(zend_standard_class_def));
	if (Z_TYPE_P(expr) == IS_ARRAY) {
		ht = zend_symtable_to_proptable(Z_ARR_P(expr));
		if (GC_FLAGS(ht) & IS_ARRAY_IMMUTABLE) {
			/* TODO: try not to duplicate immutable arrays as well ??? */
			ht = zend_array_dup(ht);
		}
		Z_OBJ_P(result)->properties = ht;
	} else if (Z_TYPE_P(expr) != IS_NULL) {
		if (UNEXPECTED(Z_TYPE_P(expr) == IS_DOUBLE && zend_isnan(Z_DVAL_P(expr)))) {
			zend_nan_coerced_to_type_warning(IS_OBJECT);
		}
		Z_OBJ_P(result)->properties = ht = zend_new_array(1);
		expr = zend_hash_add_new(ht, ZSTR_KNOWN(ZEND_STR_SCALAR), expr);
		if (op1_type == IS_CONST) {
			if (UNEXPECTED(Z_OPT_REFCOUNTED_P(expr))) Z_ADDREF_P(expr);
		} else {
			if (Z_OPT_REFCOUNTED_P(expr)) Z_ADDREF_P(expr);
		}
	}
}

static zend_always_inline void zend_cast_zval_to_array(zval *result, zval *expr, uint8_t op1_type) {
	extern ZEND_API zend_class_entry *zend_ce_closure;
	if (op1_type == IS_CONST || Z_TYPE_P(expr) != IS_OBJECT || Z_OBJCE_P(expr) == zend_ce_closure) {
		if (Z_TYPE_P(expr) != IS_NULL) {
			if (UNEXPECTED(Z_TYPE_P(expr) == IS_DOUBLE && zend_isnan(Z_DVAL_P(expr)))) {
				zend_nan_coerced_to_type_warning(IS_ARRAY);
			}
			ZVAL_ARR(result, zend_new_array(1));
			expr = zend_hash_index_add_new(Z_ARRVAL_P(result), 0, expr);
			if (op1_type == IS_CONST) {
				if (UNEXPECTED(Z_OPT_REFCOUNTED_P(expr))) Z_ADDREF_P(expr);
			} else {
				if (Z_OPT_REFCOUNTED_P(expr)) Z_ADDREF_P(expr);
			}
		} else {
			ZVAL_EMPTY_ARRAY(result);
		}
	} else if (ZEND_STD_BUILD_OBJECT_PROPERTIES_ARRAY_COMPATIBLE(expr)) {
		/* Optimized version without rebuilding properties HashTable */
		ZVAL_ARR(result, zend_std_build_object_properties_array(Z_OBJ_P(expr)));
	} else {
		HashTable *obj_ht = zend_get_properties_for(expr, ZEND_PROP_PURPOSE_ARRAY_CAST);
		if (obj_ht) {
			/* fast copy */
			ZVAL_ARR(result, zend_proptable_to_symtable(obj_ht,
				(Z_OBJCE_P(expr)->default_properties_count ||
				 Z_OBJ_P(expr)->handlers != &std_object_handlers ||
				 GC_IS_RECURSIVE(obj_ht))));
			zend_release_properties(obj_ht);
		} else {
			ZVAL_EMPTY_ARRAY(result);
		}
	}
}

ZEND_API zend_result ZEND_FASTCALL zval_update_constant(zval *pp);
ZEND_API zend_result ZEND_FASTCALL zval_update_constant_ex(zval *pp, zend_class_entry *scope);
ZEND_API zend_result ZEND_FASTCALL zval_update_constant_with_ctx(zval *pp, zend_class_entry *scope, zend_ast_evaluate_ctx *ctx);

/* dedicated Zend executor functions - do not use! */
struct _zend_vm_stack {
	zval *top;
	zval *end;
	zend_vm_stack prev;
};

/* Ensure the correct alignment before slots calculation */
ZEND_STATIC_ASSERT(ZEND_MM_ALIGNED_SIZE(sizeof(zval)) == sizeof(zval),
                   "zval must be aligned by ZEND_MM_ALIGNMENT");
/* A number of call frame slots (zvals) reserved for _zend_vm_stack. */
#define ZEND_VM_STACK_HEADER_SLOTS \
	((sizeof(struct _zend_vm_stack) + sizeof(zval) - 1) / sizeof(zval))

#define ZEND_VM_STACK_ELEMENTS(stack) \
	(((zval*)(stack)) + ZEND_VM_STACK_HEADER_SLOTS)

/*
 * In general in RELEASE build ZEND_ASSERT() must be zero-cost, but for some
 * reason, GCC generated worse code, performing CSE on assertion code and the
 * following "slow path" and moving memory read operations from slow path into
 * common header. This made a degradation for the fast path.
 * The following "#if ZEND_DEBUG" eliminates it.
 */
#if ZEND_DEBUG
# define ZEND_ASSERT_VM_STACK(stack) ZEND_ASSERT(stack->top > (zval *) stack && stack->end > (zval *) stack && stack->top <= stack->end)
# define ZEND_ASSERT_VM_STACK_GLOBAL ZEND_ASSERT(EG(vm_stack_top) > (zval *) EG(vm_stack) && EG(vm_stack_end) > (zval *) EG(vm_stack) && EG(vm_stack_top) <= EG(vm_stack_end))
#else
# define ZEND_ASSERT_VM_STACK(stack)
# define ZEND_ASSERT_VM_STACK_GLOBAL
#endif

ZEND_API void zend_vm_stack_init(void);
ZEND_API void zend_vm_stack_init_ex(size_t page_size);
ZEND_API void zend_vm_stack_destroy(void);
ZEND_API void* zend_vm_stack_extend(size_t size);

static zend_always_inline zend_vm_stack zend_vm_stack_new_page(size_t size, zend_vm_stack prev) {
	zend_vm_stack page = (zend_vm_stack)emalloc(size);

	page->top = ZEND_VM_STACK_ELEMENTS(page);
	page->end = (zval*)((char*)page + size);
	page->prev = prev;
	return page;
}

static zend_always_inline void zend_vm_init_call_frame(zend_execute_data *call, uint32_t call_info, zend_function *func, uint32_t num_args, void *object_or_called_scope)
{
	ZEND_ASSERT(!func->common.scope || object_or_called_scope);
	call->func = func;
	Z_PTR(call->This) = object_or_called_scope;
	ZEND_CALL_INFO(call) = call_info;
	ZEND_CALL_NUM_ARGS(call) = num_args;
}

static zend_always_inline zend_execute_data *zend_vm_stack_push_call_frame_ex(uint32_t used_stack, uint32_t call_info, zend_function *func, uint32_t num_args, void *object_or_called_scope)
{
	zend_execute_data *call = (zend_execute_data*)EG(vm_stack_top);

	ZEND_ASSERT_VM_STACK_GLOBAL;

	if (UNEXPECTED(used_stack > (size_t)(((char*)EG(vm_stack_end)) - (char*)call))) {
		call = (zend_execute_data*)zend_vm_stack_extend(used_stack);
		ZEND_ASSERT_VM_STACK_GLOBAL;
		zend_vm_init_call_frame(call, call_info | ZEND_CALL_ALLOCATED, func, num_args, object_or_called_scope);
		return call;
	} else {
		EG(vm_stack_top) = (zval*)((char*)call + used_stack);
		zend_vm_init_call_frame(call, call_info, func, num_args, object_or_called_scope);
		return call;
	}
}

static zend_always_inline uint32_t zend_vm_calc_used_stack(uint32_t num_args, const zend_function *func)
{
	uint32_t used_stack = ZEND_CALL_FRAME_SLOT + num_args + func->common.T;

	if (EXPECTED(ZEND_USER_CODE(func->type))) {
		used_stack += func->op_array.last_var - MIN(func->op_array.num_args, num_args);
	}
	return used_stack * sizeof(zval);
}

static zend_always_inline zend_execute_data *zend_vm_stack_push_call_frame(uint32_t call_info, zend_function *func, uint32_t num_args, void *object_or_called_scope)
{
	uint32_t used_stack = zend_vm_calc_used_stack(num_args, func);

	return zend_vm_stack_push_call_frame_ex(used_stack, call_info,
		func, num_args, object_or_called_scope);
}

static zend_always_inline void zend_vm_stack_free_extra_args_ex(uint32_t call_info, zend_execute_data *call)
{
	if (UNEXPECTED(call_info & ZEND_CALL_FREE_EXTRA_ARGS)) {
		uint32_t count = ZEND_CALL_NUM_ARGS(call) - call->func->op_array.num_args;
		zval *p = ZEND_CALL_VAR_NUM(call, call->func->op_array.last_var + call->func->op_array.T);
		do {
			i_zval_ptr_dtor(p);
			p++;
		} while (--count);
 	}
}

static zend_always_inline void zend_vm_stack_free_extra_args(zend_execute_data *call)
{
	zend_vm_stack_free_extra_args_ex(ZEND_CALL_INFO(call), call);
}

static zend_always_inline void zend_vm_stack_free_args(zend_execute_data *call)
{
	uint32_t num_args = ZEND_CALL_NUM_ARGS(call);

	if (EXPECTED(num_args > 0)) {
		zval *p = ZEND_CALL_ARG(call, 1);

		do {
			zval_ptr_dtor_nogc(p);
			p++;
		} while (--num_args);
	}
}

static zend_always_inline void zend_vm_stack_free_call_frame_ex(uint32_t call_info, zend_execute_data *call)
{
	ZEND_ASSERT_VM_STACK_GLOBAL;

	if (UNEXPECTED(call_info & ZEND_CALL_ALLOCATED)) {
		zend_vm_stack p = EG(vm_stack);
		zend_vm_stack prev = p->prev;

		ZEND_ASSERT(call == (zend_execute_data*)ZEND_VM_STACK_ELEMENTS(EG(vm_stack)));
		EG(vm_stack_top) = prev->top;
		EG(vm_stack_end) = prev->end;
		EG(vm_stack) = prev;
		efree(p);
	} else {
		EG(vm_stack_top) = (zval*)call;
	}

	ZEND_ASSERT_VM_STACK_GLOBAL;
}

static zend_always_inline void zend_vm_stack_free_call_frame(zend_execute_data *call)
{
	zend_vm_stack_free_call_frame_ex(ZEND_CALL_INFO(call), call);
}

zend_execute_data *zend_vm_stack_copy_call_frame(
	zend_execute_data *call, uint32_t passed_args, uint32_t additional_args);

static zend_always_inline void zend_vm_stack_extend_call_frame(
	zend_execute_data **call, uint32_t passed_args, uint32_t additional_args)
{
	if (EXPECTED((uint32_t)(EG(vm_stack_end) - EG(vm_stack_top)) > additional_args)) {
		EG(vm_stack_top) += additional_args;
	} else {
		*call = zend_vm_stack_copy_call_frame(*call, passed_args, additional_args);
	}
}

ZEND_API void ZEND_FASTCALL zend_free_extra_named_params(zend_array *extra_named_params);

/* services */
ZEND_API const char *get_active_class_name(const char **space);
ZEND_API const char *get_active_function_name(void);
ZEND_API const char *get_active_function_arg_name(uint32_t arg_num);
ZEND_API const char *get_function_arg_name(const zend_function *func, uint32_t arg_num);
ZEND_API const zend_function *zend_active_function_ex(const zend_execute_data *execute_data);

static zend_always_inline const zend_function *zend_active_function(void)
{
	const zend_function *func = EG(current_execute_data)->func;
	if (ZEND_USER_CODE(func->type)) {
		return zend_active_function_ex(EG(current_execute_data));
	} else {
		return func;
	}
}

ZEND_API zend_string *get_active_function_or_method_name(void);
ZEND_API zend_string *get_function_or_method_name(const zend_function *func);
ZEND_API const char *zend_get_executed_filename(void);
ZEND_API zend_string *zend_get_executed_filename_ex(void);
ZEND_API uint32_t zend_get_executed_lineno(void);
ZEND_API zend_class_entry *zend_get_executed_scope(void);
ZEND_API bool zend_is_executing(void);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_cannot_pass_by_reference(uint32_t arg_num);

ZEND_API void zend_set_timeout(zend_long seconds, bool reset_signals);
ZEND_API void zend_unset_timeout(void);
ZEND_API ZEND_NORETURN void ZEND_FASTCALL zend_timeout(void);
ZEND_API zend_class_entry *zend_fetch_class(zend_string *class_name, uint32_t fetch_type);
ZEND_API zend_class_entry *zend_fetch_class_with_scope(zend_string *class_name, uint32_t fetch_type, zend_class_entry *scope);
ZEND_API zend_class_entry *zend_fetch_class_by_name(zend_string *class_name, zend_string *lcname, uint32_t fetch_type);

ZEND_API zend_function * ZEND_FASTCALL zend_fetch_function(zend_string *name);
ZEND_API zend_function * ZEND_FASTCALL zend_fetch_function_str(const char *name, size_t len);
ZEND_API void ZEND_FASTCALL zend_init_func_run_time_cache(zend_op_array *op_array);

ZEND_API void zend_fetch_dimension_const(zval *result, const zval *container, zval *dim, int type);

ZEND_API zval* zend_get_compiled_variable_value(const zend_execute_data *execute_data_ptr, uint32_t var);

ZEND_API bool zend_gcc_global_regs(void);

#define ZEND_USER_OPCODE_CONTINUE   0 /* execute next opcode */
#define ZEND_USER_OPCODE_RETURN     1 /* exit from executor (return from function) */
#define ZEND_USER_OPCODE_DISPATCH   2 /* call original opcode handler */
#define ZEND_USER_OPCODE_ENTER      3 /* enter into new op_array without recursion */
#define ZEND_USER_OPCODE_LEAVE      4 /* return to calling op_array within the same executor */

#define ZEND_USER_OPCODE_DISPATCH_TO 0x100 /* call original handler of returned opcode */

ZEND_API zend_result zend_set_user_opcode_handler(uint8_t opcode, user_opcode_handler_t handler);
ZEND_API user_opcode_handler_t zend_get_user_opcode_handler(uint8_t opcode);

ZEND_API zval *zend_get_zval_ptr(const zend_op *opline, int op_type, const znode_op *node, const zend_execute_data *execute_data);

ZEND_API void zend_clean_and_cache_symbol_table(zend_array *symbol_table);
ZEND_API void ZEND_FASTCALL zend_free_compiled_variables(zend_execute_data *execute_data);
ZEND_API void zend_unfinished_calls_gc(zend_execute_data *execute_data, zend_execute_data *call, uint32_t op_num, zend_get_gc_buffer *buf);
ZEND_API void zend_cleanup_unfinished_execution(zend_execute_data *execute_data, uint32_t op_num, uint32_t catch_op_num);
ZEND_API ZEND_ATTRIBUTE_DEPRECATED HashTable *zend_unfinished_execution_gc(zend_execute_data *execute_data, zend_execute_data *call, zend_get_gc_buffer *gc_buffer);
ZEND_API HashTable *zend_unfinished_execution_gc_ex(zend_execute_data *execute_data, zend_execute_data *call, zend_get_gc_buffer *gc_buffer, bool suspended_by_yield);
ZEND_API zval* ZEND_FASTCALL zend_fetch_static_property(zend_execute_data *ex, int fetch_type);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_undefined_method(const zend_class_entry *ce, const zend_string *method);
ZEND_API zend_never_inline ZEND_COLD void ZEND_FASTCALL zend_non_static_method_call(const zend_function *fbc);

ZEND_API void zend_frameless_observed_call(zend_execute_data *execute_data);

zval * ZEND_FASTCALL zend_handle_named_arg(
		zend_execute_data **call_ptr, zend_string *arg_name,
		uint32_t *arg_num_ptr, void **cache_slot);
ZEND_API zend_result ZEND_FASTCALL zend_handle_undef_args(zend_execute_data *call);

#define CACHE_ADDR(num) \
	((void**)((char*)EX(run_time_cache) + (num)))

#define CACHED_PTR(num) \
	((void**)((char*)EX(run_time_cache) + (num)))[0]

#define CACHE_PTR(num, ptr) do { \
		((void**)((char*)EX(run_time_cache) + (num)))[0] = (ptr); \
	} while (0)

#define CACHED_POLYMORPHIC_PTR(num, ce) \
	(EXPECTED(((void**)((char*)EX(run_time_cache) + (num)))[0] == (void*)(ce)) ? \
		((void**)((char*)EX(run_time_cache) + (num)))[1] : \
		NULL)

#define CACHE_POLYMORPHIC_PTR(num, ce, ptr) do { \
		void **slot = (void**)((char*)EX(run_time_cache) + (num)); \
		slot[0] = (ce); \
		slot[1] = (ptr); \
	} while (0)

#define CACHED_PTR_EX(slot) \
	(slot)[0]

#define CACHE_PTR_EX(slot, ptr) do { \
		(slot)[0] = (ptr); \
	} while (0)

#define CACHED_POLYMORPHIC_PTR_EX(slot, ce) \
	(EXPECTED((slot)[0] == (ce)) ? (slot)[1] : NULL)

#define CACHE_POLYMORPHIC_PTR_EX(slot, ce, ptr) do { \
		(slot)[0] = (ce); \
		(slot)[1] = (ptr); \
	} while (0)

#define CACHE_SPECIAL (1<<0)

#define IS_SPECIAL_CACHE_VAL(ptr) \
	(((uintptr_t)(ptr)) & CACHE_SPECIAL)

#define ENCODE_SPECIAL_CACHE_NUM(num) \
	((void*)((((uintptr_t)(num)) << 1) | CACHE_SPECIAL))

#define DECODE_SPECIAL_CACHE_NUM(ptr) \
	(((uintptr_t)(ptr)) >> 1)

#define ENCODE_SPECIAL_CACHE_PTR(ptr) \
	((void*)(((uintptr_t)(ptr)) | CACHE_SPECIAL))

#define DECODE_SPECIAL_CACHE_PTR(ptr) \
	((void*)(((uintptr_t)(ptr)) & ~CACHE_SPECIAL))

#define SKIP_EXT_OPLINE(opline) do { \
		while (UNEXPECTED((opline)->opcode >= ZEND_EXT_STMT \
			&& (opline)->opcode <= ZEND_TICKS)) {     \
			(opline)--;                                  \
		}                                                \
	} while (0)

#define ZEND_CLASS_HAS_TYPE_HINTS(ce) ((bool)(ce->ce_flags & ZEND_ACC_HAS_TYPE_HINTS))
#define ZEND_CLASS_HAS_READONLY_PROPS(ce) ((bool)(ce->ce_flags & ZEND_ACC_HAS_READONLY_PROPS))


ZEND_API bool zend_verify_class_constant_type(const zend_class_constant *c, const zend_string *name, zval *constant);

ZEND_API bool zend_verify_property_type(const zend_property_info *info, zval *property, bool strict);

#define ZEND_REF_ADD_TYPE_SOURCE(ref, source) \
	zend_ref_add_type_source(&ZEND_REF_TYPE_SOURCES(ref), source)

#define ZEND_REF_DEL_TYPE_SOURCE(ref, source) \
	zend_ref_del_type_source(&ZEND_REF_TYPE_SOURCES(ref), source)

#define ZEND_REF_FOREACH_TYPE_SOURCES(ref, prop) do { \
		zend_property_info_source_list *_source_list = &ZEND_REF_TYPE_SOURCES(ref); \
		zend_property_info **_prop, **_end; \
		zend_property_info_list *_list; \
		if (_source_list->ptr) { \
			if (ZEND_PROPERTY_INFO_SOURCE_IS_LIST(_source_list->list)) { \
				_list = ZEND_PROPERTY_INFO_SOURCE_TO_LIST(_source_list->list); \
				_prop = _list->ptr; \
				_end = _list->ptr + _list->num; \
			} else { \
				_prop = &_source_list->ptr; \
				_end = _prop + 1; \
			} \
			for (; _prop < _end; _prop++) { \
				prop = *_prop; \

#define ZEND_REF_FOREACH_TYPE_SOURCES_END() \
			} \
		} \
	} while (0)

/* Add `type` as a type source on `ref` unless it is already present. Keeps the per-CV-slot
 * ADD/DEL bookkeeping balanced when the same slot is promoted more than once (already a
 * reference from `&$cv`, restored from the symbol table on re-attach, or promoted by an
 * earlier by-name write/materialization). */
static zend_always_inline void zend_ref_add_type_source_dedup(zend_reference *ref, zend_property_info *type)
{
	zend_property_info *p;
	bool found = false;
	ZEND_REF_FOREACH_TYPE_SOURCES(ref, p) {
		if (p == type) {
			found = true;
			break;
		}
	} ZEND_REF_FOREACH_TYPE_SOURCES_END();
	if (!found) {
		ZEND_REF_ADD_TYPE_SOURCE(ref, type);
	}
}

/* When a function's CV slots are exposed by name through a symbol table (each as an
 * IS_INDIRECT entry), a by-name dynamic write (`$$name = ...`, extract(), parse_str(), ...)
 * follows the indirection straight into the CV slot. A plain value slot would take the write
 * as an unchecked copy, bypassing a typed local's declared type. Promoting the slot to a
 * reference and attaching the local's synthesized type as a source routes every such write
 * through the existing typed-reference enforcement (_ZEND_TRY_ASSIGN_VALUE_EX, ZEND_ASSIGN on
 * IS_REFERENCE).
 *
 * Caller guarantees `type != NULL` (the CV is typed). Only DEFINED slots are promoted, so a
 * declared-but-unassigned typed local keeps its UNDEF state and isset()/get_defined_vars() are
 * unaffected. The type source is added at most once per slot: if the slot is already a
 * reference (aliased earlier by `&$cv`, restored from the symbol table on re-attach, or
 * promoted by an earlier call) the source is added only when not already present, so the single
 * per-CV-slot removal at frame teardown (i_free_compiled_variables() /
 * zend_detach_symbol_table()) stays balanced. */
static zend_always_inline void zend_promote_cv_to_typed_ref(zval *var, zend_property_info *type)
{
	if (Z_TYPE_P(var) == IS_UNDEF) {
		return;
	}
	if (!Z_ISREF_P(var)) {
		ZVAL_MAKE_REF_EX(var, 1);
		ZEND_REF_ADD_TYPE_SOURCE(Z_REF_P(var), type);
	} else {
		zend_ref_add_type_source_dedup(Z_REF_P(var), type);
	}
}

/* Promote every typed CV of a frame that currently holds a value to a typed reference (see
 * zend_promote_cv_to_typed_ref). Called wherever a frame's symbol table is handed out for a
 * by-name access (zend_rebuild_symbol_table, zend_attach_symbol_table, and the dynamic
 * variable fetch path), so by-name writes through the IS_INDIRECT entries are type-checked.
 * Cheap no-op (single pointer test) for the common function without typed locals, and
 * idempotent, so repeated calls across the frame's lifetime stay balanced with the single
 * per-CV-slot removal at teardown. */
static zend_always_inline void zend_promote_frame_typed_cvs(zend_execute_data *ex)
{
	const zend_op_array *op_array = &ex->func->op_array;
	zend_property_info **cv_types = op_array->cv_types;

	if (EXPECTED(cv_types == NULL)) {
		return;
	}
	zval *var = ZEND_CALL_VAR_NUM(ex, 0);
	uint32_t i, n = op_array->last_var;
	for (i = 0; i < n; i++) {
		if (UNEXPECTED(cv_types[i] != NULL)) {
			zend_promote_cv_to_typed_ref(&var[i], cv_types[i]);
		}
	}
}

/* If `slot` is a typed CV of frame `ex` that was promoted/aliased into a typed reference,
 * remove its synthesized type source. Returns true if `slot` belongs to `ex`'s CV range
 * (handled here, whether or not a source was actually removed), so a frame-walking caller
 * can stop. No-op-returning-false unless `slot` lies in this frame's CV range. */
static zend_always_inline bool zend_unset_cv_clear_type_source_in_frame(zend_execute_data *ex, zval *slot)
{
	const zend_op_array *op_array = &ex->func->op_array;
	zend_property_info **cv_types = op_array->cv_types;
	const zval *cv0 = ZEND_CALL_VAR_NUM(ex, 0);

	if (slot >= cv0 && slot < cv0 + op_array->last_var) {
		if (cv_types != NULL) {
			uint32_t idx = (uint32_t)(slot - cv0);
			if (cv_types[idx] != NULL
			 && Z_ISREF_P(slot)
			 && ZEND_REF_HAS_TYPE_SOURCES(Z_REF_P(slot))) {
				ZEND_REF_DEL_TYPE_SOURCE(Z_REF_P(slot), cv_types[idx]);
			}
		}
		return true;
	}
	return false;
}

/* A by-name unset (`unset($name)`, `unset($GLOBALS['name'])`) reached a symbol table whose
 * entry for `name` is an IS_INDIRECT pointing at the CV `slot`. The unset dtors the reference
 * through that IS_INDIRECT entry, bypassing ZEND_UNSET_CV, so a typed local's synthesized type
 * source must be removed first to keep the per-CV-slot ADD/DEL bookkeeping balanced (otherwise
 * the reference is destroyed still carrying the source and zend_reference_destroy() asserts).
 *
 * The owning frame is not necessarily the current one: `unset($GLOBALS['x'])` runs in whatever
 * function issued it, but the global symbol table's IS_INDIRECT points into the script's main
 * frame (the CV lives there). Walk the call chain to find the frame whose CV range contains
 * `slot` and clear the source there. The slot belongs to exactly one frame (CV arrays are
 * disjoint VM-stack regions), and that frame is always a live ancestor reachable through
 * prev_execute_data, so the walk terminates. Internal/dummy frames carry no op_array CVs and
 * are skipped by the range test. */
static zend_always_inline void zend_unset_cv_clear_type_source(zend_execute_data *ex, zval *slot)
{
	while (ex != NULL) {
		if (ex->func != NULL
		 && ZEND_USER_CODE(ex->func->common.type)
		 && zend_unset_cv_clear_type_source_in_frame(ex, slot)) {
			return;
		}
		ex = ex->prev_execute_data;
	}
}

/* If `slot` is a typed CV of frame `ex` and currently holds IS_UNDEF, promote it to a typed
 * reference (its synthesized type attached as a source). Returns true if `slot` belongs to
 * `ex`'s CV range (handled here, whether or not it was promoted), so a frame-walking caller
 * can stop. No-op-returning-false unless `slot` lies in this frame's CV range. */
static zend_always_inline bool zend_promote_undef_cv_to_typed_ref_in_frame(zend_execute_data *ex, zval *slot)
{
	const zend_op_array *op_array = &ex->func->op_array;
	zend_property_info **cv_types = op_array->cv_types;
	zval *cv0 = ZEND_CALL_VAR_NUM(ex, 0);

	if (slot >= cv0 && slot < cv0 + op_array->last_var) {
		if (cv_types != NULL && Z_TYPE_P(slot) == IS_UNDEF) {
			uint32_t idx = (uint32_t)(slot - cv0);
			if (cv_types[idx] != NULL) {
				ZVAL_MAKE_REF_EX(slot, 1);
				ZEND_REF_ADD_TYPE_SOURCE(Z_REF_P(slot), cv_types[idx]);
			}
		}
		return true;
	}
	return false;
}

/* A by-name dynamic WRITE (`$$name = ...`, extract() overwrite/initialize, ...) resolved an
 * IS_INDIRECT symbol-table entry straight into a CV `slot` that is still IS_UNDEF. A plain
 * value/UNDEF slot takes the write as an unchecked copy (zend_assign / ZEND_TRY_ASSIGN_*),
 * bypassing a typed local's declared type. Promoting the slot to a typed reference HERE -- at
 * the write -- attaches the local's synthesized type as a source so the assign that follows
 * routes through zend_assign_to_typed_ref()/zend_verify_ref_assignable_zval() and enforces the
 * type (coercing in weak mode, throwing in strict / on a non-coercible value). Crucially this
 * happens only on the write path: a read/isset/get_defined_vars() that observes the slot before
 * any write still sees a bare IS_UNDEF, so undefined-variable semantics are unchanged.
 *
 * The owning frame is not necessarily the current one (`$GLOBALS['x'] = ...` runs in whatever
 * function issued it, but the global symbol table's IS_INDIRECT points into the script's main
 * frame). Walk the call chain to the frame whose CV range contains `slot` and promote there, so
 * the type source is keyed by that frame's op_array->cv_types[idx] -- exactly the key the single
 * per-CV-slot removal at teardown (i_free_compiled_variables() / zend_detach_symbol_table()) and
 * by-name unset (zend_unset_cv_clear_type_source()) use, keeping ADD/DEL balanced. The slot
 * belongs to exactly one frame (CV arrays are disjoint VM-stack regions) reachable through
 * prev_execute_data, so the walk terminates. Internal/dummy frames carry no op_array CVs and are
 * skipped by the range test. Idempotent: once promoted the slot is no longer IS_UNDEF, and a
 * later by-name write that re-enters here finds a non-UNDEF slot (handled by
 * zend_assign_to_typed_ref directly), so no second source is added. */
static zend_always_inline void zend_promote_undef_cv_to_typed_ref(zend_execute_data *ex, zval *slot)
{
	while (ex != NULL) {
		if (ex->func != NULL
		 && ZEND_USER_CODE(ex->func->common.type)
		 && zend_promote_undef_cv_to_typed_ref_in_frame(ex, slot)) {
			return;
		}
		ex = ex->prev_execute_data;
	}
}

/* If `slot` is a DEFINED typed CV of frame `ex`, promote it to a typed reference (its
 * synthesized type attached as a source). Returns true if `slot` belongs to `ex`'s CV range
 * (handled here, whether or not it was promoted), so a frame-walking caller can stop. No-op-
 * returning-false unless `slot` lies in this frame's CV range. The defined-slot counterpart of
 * zend_promote_undef_cv_to_typed_ref_in_frame(); zend_promote_cv_to_typed_ref() leaves an
 * IS_UNDEF slot untouched (undefined-variable semantics preserved) and is idempotent on an
 * already-promoted slot (dedup), so the single per-CV-slot teardown removal stays balanced. */
static zend_always_inline bool zend_promote_defined_cv_to_typed_ref_in_frame(zend_execute_data *ex, zval *slot)
{
	const zend_op_array *op_array = &ex->func->op_array;
	zend_property_info **cv_types = op_array->cv_types;
	zval *cv0 = ZEND_CALL_VAR_NUM(ex, 0);

	if (slot >= cv0 && slot < cv0 + op_array->last_var) {
		if (cv_types != NULL) {
			uint32_t idx = (uint32_t)(slot - cv0);
			if (cv_types[idx] != NULL) {
				zend_promote_cv_to_typed_ref(slot, cv_types[idx]);
			}
		}
		return true;
	}
	return false;
}

/* A by-name dynamic WRITE through the GLOBAL symbol table (`$GLOBALS['name'] = ...`,
 * `$GLOBALS['name'] += ...`, `$GLOBALS['name']++`) resolved an IS_INDIRECT entry straight into a
 * CV `slot` that already holds a value. Unlike the function-local by-name path ($$name), which
 * promotes a frame's typed CVs when its symbol table is handed out (zend_get_target_symbol_table
 * -> zend_promote_frame_typed_cvs), the GLOBAL fetch returns &EG(symbol_table) directly and never
 * promotes, so a DEFINED file-scope typed local is still a plain value at the write -- the ASSIGN
 * / ASSIGN_OP / INC that follows would overwrite it unchecked, bypassing its declared type.
 * Promoting the slot to a typed reference HERE attaches the local's synthesized type as a source
 * so that store routes through zend_assign_to_typed_ref() / zend_binary_assign_op_typed_ref() /
 * zend_incdec_typed_ref() and is type-checked (coerce in weak mode, throw in strict / on a
 * non-coercible value), matching the static ($x = ...) and $$name paths. The still-UNDEF case is
 * handled separately by zend_promote_undef_cv_to_typed_ref[_rw]() on the same fetch.
 *
 * The owning frame is the script's main frame (where the global table's IS_INDIRECT points), not
 * necessarily the current one. Walk the call chain to the frame whose CV range contains `slot`
 * and promote there, so the type source is keyed by that frame's op_array->cv_types[idx] -- the
 * key the single per-CV-slot teardown removal (i_free_compiled_variables() /
 * zend_detach_symbol_table()) and by-name unset (zend_unset_cv_clear_type_source()) use, keeping
 * ADD/DEL balanced. The slot belongs to exactly one frame (CV arrays are disjoint VM-stack
 * regions) reachable through prev_execute_data, so the walk terminates. Internal/dummy frames
 * carry no op_array CVs and are skipped by the range test. Idempotent (zend_promote_cv_to_typed_ref
 * dedups), so a repeated $GLOBALS write adds no second source. */
static zend_always_inline void zend_promote_defined_cv_to_typed_ref(zend_execute_data *ex, zval *slot)
{
	while (ex != NULL) {
		if (ex->func != NULL
		 && ZEND_USER_CODE(ex->func->common.type)
		 && zend_promote_defined_cv_to_typed_ref_in_frame(ex, slot)) {
			return;
		}
		ex = ex->prev_execute_data;
	}
}

/* RW (compound-assign / inc-dec) variant of zend_promote_undef_cv_to_typed_ref_in_frame(). A
 * by-name RW write ($$name .= ..., $$name++) must first NULL-initialize the still-UNDEF slot --
 * exactly as the static typed-CV RW path does (_get_zval_ptr_cv_BP_VAR_RW / ZEND_ASSIGN_OP_TYPED)
 * after the undefined-variable warning -- so the binary op / increment runs on NULL rather than
 * IS_UNDEF (which is not a valid scalar operand and would trip ZEND_UNREACHABLE() in
 * zendi_try_convert_scalar_to_number()). For a typed CV the NULL is then wrapped in a typed
 * reference so the compound/inc-dec store routes through zend_binary_assign_op_typed_ref() /
 * zend_incdec_typed_ref() and is type-checked; for an untyped CV the slot is left as bare NULL
 * (unchecked), matching the prior behavior. Returns true once the owning frame is found. */
static zend_always_inline bool zend_promote_undef_cv_to_typed_ref_rw_in_frame(zend_execute_data *ex, zval *slot)
{
	const zend_op_array *op_array = &ex->func->op_array;
	zend_property_info **cv_types = op_array->cv_types;
	zval *cv0 = ZEND_CALL_VAR_NUM(ex, 0);

	if (slot >= cv0 && slot < cv0 + op_array->last_var) {
		ZEND_ASSERT(Z_TYPE_P(slot) == IS_UNDEF);
		ZVAL_NULL(slot);
		if (cv_types != NULL) {
			uint32_t idx = (uint32_t)(slot - cv0);
			if (cv_types[idx] != NULL) {
				ZVAL_MAKE_REF_EX(slot, 1);
				ZEND_REF_ADD_TYPE_SOURCE(Z_REF_P(slot), cv_types[idx]);
			}
		}
		return true;
	}
	return false;
}

/* RW counterpart of zend_promote_undef_cv_to_typed_ref(): see that function and
 * zend_promote_undef_cv_to_typed_ref_rw_in_frame() for the rationale. Walks the call chain to the
 * frame owning `slot` (which need not be the current one for $GLOBALS) and NULL-initializes /
 * promotes there. The slot is always a CV of some live ancestor frame, so the walk terminates. */
static zend_always_inline void zend_promote_undef_cv_to_typed_ref_rw(zend_execute_data *ex, zval *slot)
{
	while (ex != NULL) {
		if (ex->func != NULL
		 && ZEND_USER_CODE(ex->func->common.type)
		 && zend_promote_undef_cv_to_typed_ref_rw_in_frame(ex, slot)) {
			return;
		}
		ex = ex->prev_execute_data;
	}
}

/* Undo a promote-on-write that did not store anything. `slot` was promoted to a typed
 * reference wrapping IS_UNDEF by zend_promote_undef_cv_to_typed_ref() just before a by-name
 * write, and that write then failed its type check. A typed-source reference can only wrap
 * IS_UNDEF when it was freshly promoted that way (taking `&` of, or a typed property reference
 * to, an uninitialized typed slot both throw at creation), so it is solely owned by this slot
 * (refcount 1, exactly one synthesized source). Drop the type source -- keeping the per-CV-slot
 * ADD/DEL balanced -- free the reference and restore the bare IS_UNDEF slot, so a failed by-name
 * write leaves the local undefined exactly as a failed static assignment ($u = ...) does. No-op
 * unless `slot` is such a freshly-promoted reference. */
static zend_always_inline void zend_collapse_promoted_undef_ref(zval *slot)
{
	if (UNEXPECTED(Z_ISREF_P(slot))) {
		zend_reference *ref = Z_REF_P(slot);
		if (Z_TYPE(ref->val) == IS_UNDEF
		 && GC_REFCOUNT(ref) == 1
		 && ZEND_REF_HAS_TYPE_SOURCES(ref)) {
			zend_ref_del_type_source(&ZEND_REF_TYPE_SOURCES(ref), ZEND_REF_FIRST_SOURCE(ref));
			ZEND_ASSERT(!ZEND_REF_HAS_TYPE_SOURCES(ref));
			efree_size(ref, sizeof(zend_reference));
			ZVAL_UNDEF(slot);
		}
	}
}

zend_never_inline ZEND_COLD void zend_match_unhandled_error(const zval *value);

/* Call this to handle the timeout or the interrupt function. It will set
 * EG(vm_interrupt) to false.
 */
ZEND_API ZEND_COLD void ZEND_FASTCALL zend_fcall_interrupt(zend_execute_data *call);

static zend_always_inline void *zend_get_bad_ptr(void)
{
	ZEND_UNREACHABLE();
	return NULL;
}

ZEND_API void zend_return_unwrap_ref(zend_execute_data *call, zval *return_value);

END_EXTERN_C()

#endif /* ZEND_EXECUTE_H */
