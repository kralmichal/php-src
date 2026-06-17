/* This is a generated file, edit int.stub.php instead.
 * Stub hash: 97c9f6c1894cef49d4c5f397bd12c4b16f584a32 */

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_MASK_EX(arginfo_class_Int_pow, 0, 2, MAY_BE_LONG|MAY_BE_DOUBLE)
	ZEND_ARG_TYPE_INFO(0, num, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, exponent, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Int_abs, 0, 1, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, num, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(Int, pow);
ZEND_METHOD(Int, abs);

static const zend_function_entry class_Int_methods[] = {
	ZEND_ME(Int, pow, arginfo_class_Int_pow, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Int, abs, arginfo_class_Int_abs, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_Int(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "Int", class_Int_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES);

	return class_entry;
}
