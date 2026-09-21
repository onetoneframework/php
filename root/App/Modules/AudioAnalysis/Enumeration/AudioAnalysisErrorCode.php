<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Enumeration;

enum AudioAnalysisErrorCode: string
{
	case UPLOAD_REJECTED = 'upload_rejected';
	case SERVICE_TIMEOUT = 'service_timeout';
	case SERVICE_UNAVAILABLE = 'service_unavailable';
	case INVALID_SERVICE_RESPONSE = 'invalid_service_response';
}
