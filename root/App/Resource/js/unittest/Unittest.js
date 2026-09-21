(function ($, core) {
	console.log(window);

	core.Evt.setDocumentHiddenListener(function () {
		console.log('Document is ' + (core.Evt.isDocumentHidden() ? 'hidden' : 'restored') + ' ' + core.Time.formatTime(core.Time.getTime()));
	});

	core.Evt.addListener(window, 'load', function () {

		$.core.WebCam.captureVideo("#webcam");

		$('#append').append('<h1>Browser Type</h1>');

		$('#append').append('isFirefox : ');
		$('#append').append(core.Browser.isFirefox() === true ? 'true' : 'false');

		$('#append').append('</br>isNetscape : ');
		$('#append').append(core.Browser.isNetscape() === true ? 'true' : 'false');

		$('#append').append('</br>isOpera : ');
		$('#append').append(core.Browser.isOpera() === true ? 'true' : 'false');

		$('#append').append('</br>isChrome : ');
		$('#append').append(core.Browser.isChrome() === true ? 'true' : 'false');

		$('#append').append('</br>isIE : ');
		$('#append').append(core.Browser.isIE() === true ? 'true' : 'false');


		$('#append').append('</br></br><h1>Computer Type</h1>');


		$('#append').append('isWin : ');
		$('#append').append(core.Browser.isWindows() === true ? 'true' : 'false');

		$('#append').append('</br>isMac : ');
		$('#append').append(core.Browser.isMac() === true ? 'true' : 'false');


		$('#append').append('</br></br><h1>Mobile Status</h1>');


		$('#append').append('isMobile : ');
		$('#append').append(core.Mobile.isMobile() === true ? 'true' : 'false');


		$('#append').append('</br></br><h1>Browser Status</h1>');


		$('#append').append('SWF Installed : ');
		$('#append').append(core.Flash.isSupport());

		$('#append').append('</br>Popup Enabled : ');
		$('#append').append(core.Popup.Enabled() === true ? "true" : "false");

		$('#append').append('</br>Cookie Enabled : ');
		$('#append').append(core.Cookie.isAccept());

		$('#append').append('</br>FileReader Enabled : ');
		$('#append').append(core.File.isSupport());

		$('#append').append('</br>Notify Enabled : ');
		$('#append').append(core.Browser.isFileProtocol() === true ? "false" : core.Notify.getPermit() === true ? 'true' : 'false');

		$('#append').append('</br>Storage Enabled : ');
		$('#append').append(core.Storage.isSupport() === true ? 'true' : 'false');

		$('#append').append('</br>GEO Enabled : ');
		$('#append').append(core.GEO.isSupport());

		$('#append').append('</br>Battery Enabled : ');
		$('#append').append(core.Battery.isGet() === true ? 'true' : 'false');

		$('#append').append('</br>Browser is Mozila : ');
		$('#append').append(core.Browser.isMozila() === true ? 'true' : 'false');

		$('#append').append('</br>isSupportCssAnimation : ');
		$('#append').append(core.Browser.isSupportCssAnimation() === true ? 'true' : 'false');

		$('#append').append('</br>isFullScreen : ');
		$('#append').append(core.Screen.isFullScreen(window) === true ? 'true' : 'false');

		$('#append').append('</br>isConsoleAvailable : ');
		$('#append').append(core.Browser.isConsoleAvailable());

		$('#append').append('</br>isUserNeedPayCost : ');
		$('#append').append(core.Browser.isUserNeedPayCost() === true ? 'true' : 'false');

		$('#append').append('</br>hasIframe : ');
		$('#append').append(core.Browser.hasIframe() === true ? 'true' : 'false');

		$('#append').append('</br>hasGetUserMedia : ');
		$('#append').append(core.Browser.hasGetUserMedia() === true ? 'true' : 'false');

		$('#append').append('</br>isOnline : ');
		$('#append').append(core.Browser.isOnline());

		$('#append').append('</br>getBrowserType : ');
		$('#append').append(core.Browser.getType());

		$('#append').append('</br>Audio is Support : ');
		$('#append').append(core.Audio.isSupport());

		$('#append').append('</br>webDB is Support : ');
		$('#append').append(core.WebDB.isSupport() === true ? 'true' : 'false');

		$('#append').append('</br>WebSocket is Support : ');
		$('#append').append(core.WebSocket.isSupport() === true ? 'true' : 'false');

		$("#append").appendDiv('eventListener');
		$("#append").appendDiv('notifyTab');
		$("#append").appendDiv('keyDownHandler');
		$("#append").appendDiv('getTime');


		core.Evt.onBackSpaceClick(function (ret) {
			console.log(ret);
		});


		core.Timer.interval(function (e) {
			$('.getTime').text(core.Time.getTimeArr().join(":"));
		}, 0.1, '');

		$('.notifyTab').click(function () {
			core.Browser.isFileProtocol() === true
				? alert("Not Supported on File Protocol")
				: core.Notify.Show('Message', 'Hello World', "favicon.ico", "Hello", {
					body: "Hello",
					icon: "notify.png",
					vibrate: 200,
					dir: 'rtl'
				});
		});

		if (!$('.notifyTab').text()) {
			$('.notifyTab').text('Show Notify');
		}

		if (!$('.keyDownHandler').text()) {
			$('.keyDownHandler').text('KeyCode : 0');
		}

		$(window).keyDownHandler(function (keyCode, event) {
			if (keyCode != '') {
				$('.keyDownHandler').text('keyCode : ' + keyCode);
			}
		});

		$(window).getEvent(window, function (e) {
			$('.eventListener').text('originalEvent : ' + e.originalEvent + ' type : ' + e.type + ' target : ' + e.target + ' currentTarget : ' + e.currentTarget);
		});
	});

	QUnit.config.hidepassed = false;
	QUnit.config.autostart = true;
	QUnit.config.altertitle = false;
	QUnit.config.collapse = false;
	QUnit.config.notrycatch = true;
	QUnit.config.scrolltop = false;
	QUnit.config.testTimeout = 1000;

	//<!--Application-->
	QUnit.module("Application");

	QUnit.test("App.fnCombine", function (assert) {

		var fn = {
			'unitTest': function () {
				return function () {
					return 'success'
				}
			},
			'Application': function () {
				return function () {
					return 'success'
				}
			}
		};

		assert.ok(core.App.fnCombine(fn)['unitTest'].call() == 'success', "Result is " + core.App.fnCombine(fn)['unitTest'].call());
	});

	//<!--Array-->
	QUnit.module("Array");

	QUnit.test("Arr.canPush", function (assert) {
		assert.ok(core.Arr.canPush() === true, "Passed!");
	});

	QUnit.test("Arr.isArrayEqual", function (assert) {
		assert.ok(core.Arr.isArrayEqual(['1', '2', '3'], ['1', '2', '3']) === true, "Passed!");
	});

	QUnit.test("Arr.Search", function (assert) {
		assert.ok(core.Arr.findObject(['1', '2', '3'], '3') === true && core.Arr.findObject(['1', '2', '3'], '4') === false, "Passed!");
	});

	//<!--String-->
	QUnit.module("String");

	QUnit.test("Str.isBase64", function (assert) {
		assert.ok(core.Str.isBase64("dGVzdA==") === true, "Passed!");
	});

	QUnit.test("Str.addBin", function (assert) {
		assert.ok(core.Str.addBin("100101", "11111") === "1000100", "Passed!");
	});

	QUnit.test("Str.andBin", function (assert) {
		assert.ok(core.Str.andBin("100101", "11111") === "000101", "Passed!");
	});

	QUnit.test("Str.orBin", function (assert) {
		assert.ok(core.Str.orBin("100101", "10110") === "110111", "Passed!");
	});

	QUnit.test("Str.xorBin", function (assert) {
		assert.ok(core.Str.xorBin("100101", "10110") === "110011", "Passed!");
	});

	QUnit.test("Str.randomStr", function (assert) {
		assert.ok(core.Str.randomStr(10).length === 10, "Passed!");
	});

	QUnit.test("Str.escape", function (assert) {
		assert.ok(core.Str.escape('\'<>"=&') === "&#x27;&lt;&gt;&quot;=&amp;", "Passed!");
	});

	QUnit.test("Str.recovery, Str.escape", function (assert) {
		assert.ok(core.Str.recovery(core.Str.escape('\'<>"=&')) === '\'<>"=&', "Passed!");
	});

	QUnit.test("Str.cut", function (assert) {
		assert.ok(core.Str.cut('Hello World', 5) === "Hello", "Passed!");
	});

	QUnit.test("Str.lcase", function (assert) {
		assert.ok(core.Str.lcase('Hello World') === "hello world", "Passed!");
	});

	QUnit.test("Str.cutStr", function (assert) {
		assert.ok(core.Str.getUniqueRand(2, 100, 10).length === 10, "Passed!");
	});

	QUnit.test("Str.cutStr", function (assert) {
		assert.ok(core.Str.cutStr('Hello World', 5) === "He...", "Passed!");
	});

	QUnit.test("Str.ucase", function (assert) {
		assert.ok(core.Str.ucase('Hello World') === "HELLO WORLD", "Passed!");
	});

	QUnit.test("Str.length", function (assert) {
		assert.ok(core.Str.length('Hello World') === 11, "Passed!");
	});

	QUnit.test("Str.trim", function (assert) {
		assert.ok(core.Str.trim('  Hello World   ') === "Hello World", "Passed!");
	});

	QUnit.test("Str.ltrim", function (assert) {
		assert.ok(core.Str.ltrim('  Hello World   ') === "Hello World   ", "Passed!");
	});

	QUnit.test("Str.rtrim", function (assert) {
		assert.ok(core.Str.rtrim('  Hello World   ') === "  Hello World", "Passed!");
	});

	QUnit.test("Str.replaceAll", function (assert) {
		assert.ok(core.Str.replaceAll('ABAACD', "A", "C") === "CBCCCD", "Passed!");
	});

	QUnit.test("Str.ucFirst", function (assert) {
		assert.ok(core.Str.ucFirst('hello') === "Hello", "Passed!");
	});

	//<!--Time-->
	QUnit.module("String");

	QUnit.test("Time.formatTime", function (assert) {
		assert.ok(core.Time.formatTime('120') === "02:00", "Passed!");
	});

	QUnit.test("Time.monthToNumbar", function (assert) {
		assert.ok(core.Time.monthToNumbar("Jan") === "01", "Passed!");
	});

	//<!--URL-->
	QUnit.module("URL");

	QUnit.test("URL.getJoinChar", function (assert) {
		assert.ok(core.URL.getJoinChar("http://www.test.com/?") === "&", "Passed!");
	});

	QUnit.test("URL.getUrlVars", function (assert) {
		assert.ok(core.URL.getUrlVars("http://test.com/index.jsp?a=1&b=3").length === 2, "Passed!");
	});

	QUnit.test("URL.parseQuerystring", function (assert) {
		assert.ok(core.URL.parseQuerystring("http://test.com/index.jsp?aewr=1&bsdfsdf=3&wejrow=3")['wejrow'] === "3", "Passed!");
	});

	QUnit.test("URL.changeSrcDirectory", function (assert) {
		assert.ok(core.URL.changeSrcDirectory("<img src=\"http://www.naver.com/a.jpg\"></img>", "test.jpg") === "<img src=\"test.jpg\"></img>", "Passed!");
	});

	QUnit.test("URL.setQuery", function (assert) {
		assert.ok(core.URL.setQuery("a", "test", "http://www.test.com/a=1") === "http://www.test.com/a=1?a=test", "Passed!");
	});

	QUnit.test("URL.getParams", function (assert) {
		assert.ok(core.URL.getParams("http://www.test.com/index.jsp?a=1&b=2&c=3").length === 3, "Passed!");
	});

	//<!--Browser-->
	QUnit.module("Browser");

	QUnit.test("Browser.getTitle", function (assert) {
		assert.ok(core.Browser.getTitle() === "Test Page", "Browser title is " + core.Browser.getTitle());
	});

	QUnit.test("Browser.getCharSet", function (assert) {
		assert.ok(core.Browser.getCharSet() === "utf-8", "Document Char Set is " + core.Browser.getCharSet());
	});

	QUnit.test("Browser.getWindowsVersion", function (assert) {
		assert.ok(core.Browser.getWindowsVersion(), "Window Version is " + core.Browser.getWindowsVersion());
	});

	QUnit.test("Browser.getType", function (assert) {
		assert.ok(core.Browser.getType(), "Browser is " + core.Browser.getType());
	});

	QUnit.test("Browser.getDetail", function (assert) {
		assert.ok(core.Browser.getDetail(), "Browser is " + core.Browser.getDetail().Name + ", Version is " + core.Browser.getDetail().Version);
	});

	//<!--Element-->
	QUnit.module("Element");

	QUnit.test("Element.isWheelExists", function (assert) {
		assert.ok(core.Element.isWheelExists(), "Wheel is " + (core.Element.isWheelExists() ? "supported" : "not supported"));
	});

	QUnit.test("Element.getWebColor", function (assert) {
		assert.ok(core.Element.getWebColor("ashgrey") === "B2BEB5", "Ashgrey WebColor Hex Code : " + core.Element.getWebColor("ashgrey"));
	});

	QUnit.test("Element.getRectangle", function (assert) {
		assert.notDeepEqual(core.Element.getRectangle("body"), {
			offset_left: 0,
			offset_top: 0,
			position_left: 0,
			position_top: 0,
			width: 1000
		}, "Passed!");
	});

	QUnit.test("Element.getInnerWidth", function (assert) {
		assert.notDeepEqual(core.Element.getInnerWidth("body"), 0, "Body Inner Width is " + core.Element.getInnerWidth("body"));
	});

	QUnit.test("Element.getInnerHeight", function (assert) {
		assert.notDeepEqual(core.Element.getInnerHeight("body"), 0, "Body Inner Height is " + core.Element.getInnerHeight("body"));
	});

	QUnit.test("Element.getScrollTop", function (assert) {
		assert.ok(core.Element.getScrollTop() > -1, "Scroll Top is " + core.Element.getScrollTop());
	});

	QUnit.test("Element.getScrollLeft", function (assert) {
		assert.ok(core.Element.getScrollLeft() > -1, "Scroll Left is " + core.Element.getScrollLeft());
	});

	QUnit.test("Element.getWidth", function (assert) {
		assert.ok(core.Element.getWidth("body") > -1, "Body Width is " + core.Element.getWidth("body"));
	});

	QUnit.test("Element.getOffset", function (assert) {
		assert.notDeepEqual(core.Element.getOffset("body"), {
			left: 0,
			top: 0
		}, "Passed!");
	});

	QUnit.test("Element.getLeft", function (assert) {
		assert.notDeepEqual(core.Element.getLeft("body") > -1, {
			left: 0,
			top: 0
		}, "Body Left is " + core.Element.getLeft("body"));
	});

	QUnit.test("Element.getTop", function (assert) {
		assert.notDeepEqual(core.Element.getTop("body") > -1, {
			left: 0,
			top: 0
		}, "Body Top is " + core.Element.getTop("body"));
	});

	QUnit.test("Element.getinnerHTML", function (assert) {
		assert.ok(core.Element.getinnerHTML("head") == undefined, "Passed!");
	});

	QUnit.test("Element.isWheelExists", function (assert) {
		assert.ok(core.Element.isWheelExists() === true ? true : core.Element.isWheelExists() === false, "Passed!");
	});

	QUnit.test("Element.getKeyDownCode", function (assert) {
		assert.ok(core.Element.getKeyDownCode("k") === 75, "Passed!");
	});

	//<!--SimpleCrypto-->
	QUnit.module("SimpleCrypto");

	QUnit.test("SimpleCrypto.getRandomUint32Values", function (assert) {
		assert.ok(core.SimpleCrypto.getRandomUint32Values(10).length === 10, "Passed!");
	});

	QUnit.test("SimpleCrypto.getSecureLink", function (assert) {
		assert.ok(core.SimpleCrypto.getSecureLink("test/a32j", 72000000), "Passed!");
	});

	QUnit.test("SimpleCrypto.AESDecrypt, SimpleCrypto.AESEncrypt", function (assert) {
		assert.ok(core.SimpleCrypto.AESDecrypt("1234", core.SimpleCrypto.AESEncrypt("data", "1234")) === "data", "Passed!");
	});

	QUnit.test("SimpleCrypto.hexToNum, SimpleCrypto.numToHex", function (assert) {
		assert.ok(core.SimpleCrypto.hexToNum(core.SimpleCrypto.numToHex(104278)) === 104278, "Passed!");
	});

	QUnit.test("SimpleCrypto.asciiDecode, SimpleCrypto.asciiEncode", function (assert) {
		assert.ok(core.SimpleCrypto.asciiDecode(core.SimpleCrypto.asciiEncode("test1000", 10), 10) === "test1000", "Passed!");
	});

	//<!--Cookie-->
	QUnit.module("Cookie");

	QUnit.test("Cookie.Set, Cookie.Get", function (assert) {
		assert.ok(core.Browser.isFileProtocol() === true ? true : (core.Cookie.Set("test", "123", 300), core.Cookie.Get("test") === "123"), "Passed!");
	});

	//<!--Event-->
	QUnit.module("Event");

	QUnit.test("Event.getCallerScriptPath", function (assert) {
		assert.ok(core.Evt.getCallerScriptPath(), core.Evt.getCallerScriptPath());
	});
})(jQuery, $.core);