enum {
    CV_CAP_ANY = 0
};
enum {
        CV_CAP_PROP_FRAME_WIDTH = 3,
        CV_CAP_PROP_FRAME_HEIGHT = 4
};

typedef struct CvCapture CvCapture;
typedef struct IplImage IplImage;
CvCapture* cvCreateCameraCapture(int index);
IplImage* cvQueryFrame(CvCapture* capture);
int cvSaveImage(const char *filename, const IplImage *image);
void cvReleaseCapture (CvCapture **capture);
int cvSetCaptureProperty(CvCapture* capture, int property_id, double value);
void cvFlip(const IplImage* src, IplImage* dst, int flip_mode);
