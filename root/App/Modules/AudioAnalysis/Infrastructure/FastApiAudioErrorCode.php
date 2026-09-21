<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Infrastructure;

enum FastApiAudioErrorCode: string
{
	case EMPTY_UPLOAD = 'empty_upload';
	case FILE_TOO_LARGE = 'file_too_large';
	case INVALID_WAVE = 'invalid_wave';
	case UNSUPPORTED_MEDIA_TYPE = 'unsupported_media_type';
	case UNSUPPORTED_RECORDING = 'unsupported_recording';
	case ANALYSIS_REJECTED = 'analysis_rejected';
	case INVALID_REQUEST = 'invalid_request';
	case INTERNAL_ERROR = 'internal_error';
	case SERVICE_BUSY = 'service_busy';
}
