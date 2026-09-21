HRESULT RoInitialize(uint32_t initType);

HRESULT WindowsCreateString(const wchar_t* src, uint32_t length, HSTRING* string);

HRESULT WindowsDeleteString(HSTRING string);

HRESULT RoGetActivationFactory(HSTRING className, void* iid, void** factory);

  // IActivationFactory
  //HRESULT IActivationFactory_CreateInstance(IActivationFactory* self, void** instance);

  // IGeolocator
  //HRESULT IGeolocator_GetGeopositionAsync(IGeolocator* self, IAsyncOperation** op);

  // IAsyncOperation<TResult>
  //HRESULT IAsyncOperation_GetResults(IAsyncOperation* self, IGeoposition** result);

  // IGeoposition
  //HRESULT IGeoposition_get_Coordinate(IGeoposition* self, IPosition** coord);

  // IPosition
  //HRESULT IPosition_GetLatitude(IPosition* self, double* latitude);
  //HRESULT IPosition_GetLongitude(IPosition* self, double* longitude);
