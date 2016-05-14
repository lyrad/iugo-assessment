<?php
namespace App;

class ApiException extends \Exception
{
	const CODE_KERNEL_PANIC = "5001";
	const MESSAGE_KERNEL_PANIC = "API Kernel Panic. Please contact support.";

	const CODE_ENDPOINT_NOT_EXISTS = 5002;
	const MESSAGE_ENDPOINT_NOT_EXISTS = "Endpoint %s does not exist.";

	const CODE_ENDPOINT_NOT_IMPLEMENTED = 5003;
	const MESSAGE_ENDPOINT_NOT_IMPLEMENTED = "Endpoint %s is not implemented.";

	const CODE_INPUT_JSON = 5004;
	const MESSAGE_INPUT_JSON = "JSON input paramter does not contain a valid JSON string. Please check your request.";

	const CODE_INPUT_MISSING = 5005;
	const MESSAGE_INPUT_MISSING = "An input parameter required is missing. Please check your request.";

	const CODE_INPUT_TYPE = 5006;
	const MESSAGE_INPUT_TYPE = "An input parameter has not the correct type. Please check your request.";
}

