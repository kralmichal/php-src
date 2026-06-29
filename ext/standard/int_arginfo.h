/* This is a generated file, edit int.stub.php instead.
 * Stub hash: 463ca08b3cc598d90070e31cfcc817867daf77fc */

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_MASK_EX(arginfo_class_Int_pow, 0, 2, MAY_BE_LONG|MAY_BE_DOUBLE)
	ZEND_ARG_TYPE_INFO(0, num, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, exponent, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_MASK_EX(arginfo_class_Int_abs, 0, 1, MAY_BE_LONG|MAY_BE_DOUBLE)
	ZEND_ARG_TYPE_INFO(0, num, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Int_clamp, 0, 3, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, num, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, min, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, max, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(Int, pow);
ZEND_METHOD(Int, abs);
ZEND_METHOD(Int, clamp);

static const zend_function_entry class_Int_methods[] = {
	ZEND_ME(Int, pow, arginfo_class_Int_pow, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Int, abs, arginfo_class_Int_abs, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Int, clamp, arginfo_class_Int_clamp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_Int(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "Int", class_Int_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES);

	return class_entry;
}
