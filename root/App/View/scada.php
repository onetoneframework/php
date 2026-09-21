<div id="root"></div>

<div id="wrap" style="display: inline-flex;height:100%">
    <div id="navi" style="width:250px;height: 720px;overflow-y: scroll;overflow-x: unset;position:sticky">
        <?php foreach ($thumbnails as $key => $thumbnail): ?>
            <?php $page = str_replace('.xml', '', $pages[$key]); ?>
            <a href="<?php echo $page; ?>">
                <div id="<?php echo $page; ?>"
                    style="margin:10px;width:215px;height:150px;background-image:url('/App/File/X8/thumbnails/<?php echo $thumbnail; ?>');background-size: 100% 100%;">
                </div>

                <p style="width:100%;text-align:center;padding: 0px;margin: 0px;line-height: 6px;font-size: 14px;">
                    <?php echo $page; ?>
                </p>
            </a>
        <?php endforeach; ?>
    </div>

    <div id="display" style="height: 720px;overflow-y: scroll;">

        <div style="position:relative;width:1920px;height:900px;overflow:hidden">
            <div class="nav" style="background-position-x:0px"></div>
            <?php foreach ($assets as $asset): ?>
                <?php if ($asset['type'] == 'text'): ?>
                    <?php $isExtern = false; ?>
                    <?php if ($asset['text'] == 'P' || $asset['text'] == 'M' || $asset['text'] == 'F'): ?>
                    <?php $isExtern = true; ?>
                    <?php endif;?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle hover_clear text_node">
                        <?php if ($asset['text'] == 'P' || $asset['text'] == 'M' || $asset['text'] == 'F'): ?>
                            <div style="position:relative;">
                                <div class="lamp"></div>
                                <div style="position: relative;font-weight:bold;">
                                    <?php echo $asset['text']; ?>
                                </div>
                                <div class="pump"></div>
                            </div>
                        <?php elseif ($asset['text'] == '100'): ?>
                            <div style="opacity:0.5;background-color: #92411c;color: yellow;">
                                <?php echo $asset['tag']; ?>
                            </div>
                        <?php else: ?>
                            <div class="visible_text">
                                <?php 
                                    $tagPlain = str_replace(['text', 'group'], '', $asset['attributes']['name']);
                                    if(isset($asset['attributes']['tag']) && $asset['attributes']['tag'] != $tagPlain){?>
                                    *
                                <?php }?>
                                <?php echo isset($asset['attributes']['tag']) ? $asset['attributes']['tag'] :  $asset['text']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($asset['type'] == 'image'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle"></div>
                <?php elseif ($asset['type'] == 'rectangle'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle"></div>
                <?php elseif ($asset['type'] == 'ellipse'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle"></div>
                <?php elseif ($asset['type'] == 'line'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle"></div>
                <?php elseif ($asset['type'] == 'cubicbezier'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle cubicbezier">
                        <svg height="900" width="1920" xmlns="http://www.w3.org/2000/svg" style="">
                            <path d="<?php echo $asset['attributes']['points']; ?>"/>
                        </svg>
                    </div>
                <?php elseif ($asset['type'] == 'polyline'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle polyline">
                        <svg height="900" width="1920" xmlns="http://www.w3.org/2000/svg" style="">
                            <polyline points="<?php echo $asset['attributes']['points']; ?>" />
                        </svg>
                    </div>
                <?php elseif ($asset['type'] == 'triangle'): ?>
                    <div id="<?php echo $asset['attributes']['name']; ?>" class="toggle triangle"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    let isClicked = false;
    let {prevClientY, prevClientX} = 0;
    const body = document.getElementById("display");
    const wrap = document.getElementById("wrap");
    const navi = document.getElementById("navi");

    window.addEventListener('mousedown', function (e) {
        isClicked = true;

        const { clientY, clientX } = e;
        prevClientY = clientY;
        prevClientX = clientX;

        prevClientX += wrap.scrollLeft;
        prevClientX -= navi.clientWidth;
        prevClientY += body.scrollTop;
    });
    window.addEventListener('mouseup', function (e) {
        isClicked = false;
    });

    window.addEventListener('mousemove', function (e) {
        const { clientY, clientX } = e;

        document.getElementsByClassName('nav')[0].style.backgroundPositionX = `-${clientX}px`;
        document.getElementsByClassName('nav')[0].style.backgroundPositionY = `-${clientY - 50}px`;

        if (!isClicked) {
            return;
        }

        //wrap.scrollLeft = clientX - prevClientX;
        body.scrollTop = clientY - prevClientY;
        document.body.scrollLeft = 100;
    });

    var toggles = document.getElementsByClassName('toggle');
    for (var i = 0; i < toggles.length; i++) {
        toggles[i].addEventListener('click', function (e) {
            var target = e.target.parentElement;
            const id = target.attributes.id.textContent;
            const currentPage = window.location.pathname.replace("/", "");

            if (target.className == 'target') {
            } else {
                target.className = 'target on';

                window.localStorage.setItem(id + "_" + currentPage, "on");
            }

            e.preventDefault();
        });
    }

    function focusSection(id) {
        const element = document.getElementById(id);
        element.classList.add('focused');
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => element.classList.remove('focused'), 2000); // Remove highlight after 2 seconds
    }

    document.addEventListener('DOMContentLoaded', function () {
        const link = window.location.pathname;
        focusSection(link.substr(1));

        const texts = document.getElementsByClassName("text_node");

        for(var i = 0; i < texts.length; i++) {
            var text = texts[i];

            const id = text.attributes.id.textContent;
            if (!id.startsWith("text_")) {
                continue;
            }

            const currentPage = window.location.pathname.replace("/", "");
            const localStorageValue = window.localStorage.getItem(id + "_" + currentPage);

            if (localStorageValue != null) {
                if (localStorageValue == 'on') {
                    text.classList.add("focus");
                }
            }
        }
    });
</script>

<style>
    <?php foreach ($assets as $asset): ?>
        <?php if ($asset['type'] == 'text'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                z-index: 99999;
                font-family: '돋움';
                position: absolute;
                font-size: <?php echo $asset['attributes']['size']; ?>px;
                color: <?php echo $asset['attributes']['fillColor']; ?>;
                left: <?php echo $asset['attributes']['x']; ?>px;
                top: <?php echo $asset['attributes']['y']; ?>px;
                width: <?php echo $asset['attributes']['width']; ?>px;
                height: <?php echo $asset['attributes']['height']; ?>px;
                text-align: <?php echo $asset['attributes']['align']; ?>;
            }

        <?php elseif ($asset['type'] == 'image'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                background-size: 100% 100%;
                background-image: url('/App/File/X8/files/<?php echo $asset['attributes']['url']; ?>');
                position: absolute;
                left: <?php echo $asset['attributes']['x']; ?>px;
                top: <?php echo $asset['attributes']['y']; ?>px;
                <?php echo ($asset['attributes']['target'] == 'vertical' ? 'border-left' : 'border-top'); ?>: 0px solid #000;
                width: <?php echo $asset['attributes']['width']; ?>px;
                height: <?php echo $asset['attributes']['height']; ?>px;
                transform: rotate(<?php echo $asset['attributes']['angle']; ?>deg);
            }

        <?php elseif ($asset['type'] == 'rectangle'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                position: absolute;
                transform: rotate(<?php echo $asset['attributes']['angle']; ?>deg);
                border-radius: <?php echo $asset['attributes']['radius']; ?>px;
                border: 1px <?php echo $asset['attributes']['lineStyle']; ?> <?php echo $asset['attributes']['strokeColor']; ?>;
                background-color: <?php echo $asset['attributes']['fillColor']; ?>;
                left: <?php echo $asset['attributes']['x']; ?>px;
                top: <?php echo $asset['attributes']['y']; ?>px;
                width: <?php echo $asset['attributes']['width']; ?>px;
                height: <?php echo $asset['attributes']['height']; ?>px;
                mix-blend-mode: color-burn;
            }

        <?php elseif ($asset['type'] == 'ellipse'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                position: absolute;
                border: <?php echo $asset['attributes']['lineWidth']; ?>px solid <?php echo $asset['attributes']['strokeColor']; ?>;
                background-color: <?php echo $asset['attributes']['fillColor']; ?>;
                left: <?php echo $asset['attributes']['x']; ?>px;
                top: <?php echo $asset['attributes']['y']; ?>px;
                width: <?php echo $asset['attributes']['width']; ?>px;
                height: <?php echo $asset['attributes']['height']; ?>px;
                border-radius: 50%;
            }

        <?php elseif ($asset['type'] == 'line'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                position: absolute;
                <?php if ($asset['attributes']['lineStyle'] == 'dashed'): ?>
                    background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' stroke='<?php echo $asset['attributes']['strokeColor']; ?>' stroke-width='<?php echo $asset['attributes']['lineWidth']; ?>' stroke-dasharray='2%2c 8%2c 8' stroke-dashoffset='0' stroke-linecap='butt'/%3e%3c/svg%3e");
                <?php else: ?>
                    <?php echo ($asset['attributes']['target'] == 'vertical' ? 'border-left' : 'border-top'); ?>: <?php echo $asset['attributes']['lineWidth']; ?>px <?php echo $asset['attributes']['lineStyle']; ?> <?php echo $asset['attributes']['strokeColor']; ?>;
                <?php endif; ?>
                left: <?php echo $asset['attributes']['x']; ?>px;
                top: <?php echo $asset['attributes']['y']; ?>px;
                width: <?php echo $asset['attributes']['width']; ?>px;
                height: <?php echo $asset['attributes']['height']; ?>px
            }

        <?php elseif ($asset['type'] == 'polyline' || $asset['type'] == 'cubicbezier'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                position: absolute;
                width: 1920px;
                top: 0px;
                left: 0px
            }

            #<?php echo $asset['attributes']['name']; ?> <?php echo ($asset['type'] == 'polyline') ? "polyline" : "path" ?> 
            {
                transform: rotate(<?php echo $asset['attributes']['angle']; ?>deg);
                fill:<?php echo $asset['attributes']['fillColor']; ?>;
                mix-blend-mode: overlay;
            }
        <?php elseif ($asset['type'] == 'triangle'): ?>

            #<?php echo $asset['attributes']['name']; ?> 
            {
                position: absolute;
                left: <?php echo $asset['attributes']['x']; ?>px;
                top: <?php echo $asset['attributes']['y']; ?>px;
                transform: rotate(<?php echo $asset['attributes']['angle']; ?>deg);
                border-left: <?php echo $asset['attributes']['width'] / 2; ?>px solid transparent;
                border-right: <?php echo $asset['attributes']['width'] / 2; ?>px solid transparent;
                border-bottom: <?php echo $asset['attributes']['height']; ?>px solid <?php echo $asset['attributes']['fillColor']; ?>
            }

        <?php endif; ?>
    <?php endforeach; ?>


    polyline {
        stroke: #000;
        stroke-width: 1;
        transform-box: fill-box;
        transform-origin: center;
    }

    path {
        transform-box: fill-box;
        transform-origin: center;
    }

    .focus {
        border: 2px solid red;
    }

    .pump {
        /*background-color: #0cf33a;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        top: -19px;
        left: -3px;
        z-index: 1;
        position: relative;
        border: 1px solid black;*/
    }

    .on {
        text-decoration: underline;
        color: red !important;
        border-color: #ff0000 !important;
    }

    .on text {
        text-decoration: underline;
        fill: red !important;
    }

    .toggle {
        cursor: pointer;
        user-select: none; /* Standard */
        -webkit-user-select: none; /* Chrome, Safari */
        -moz-user-select: none; /* Firefox */
        -ms-user-select: none; /* Internet Explorer/Edge */
    }

    .toggle:hover {
        color: blue !important;
    }

    .nav {
        background: url(/App/View/img.png);
        width: 400px;
        height: 400px;
        position: fixed;
        right: 0px;
        display: none;
    }

    body {
        padding: 0px;
        margin: 0px;
        background-color: #e2e2e2;
    }

    .lamp {
        width: 9px;
        height: 11px;
        animation: colorCycle 3s infinite;
        position: absolute;
        top: 15px;
        left: 25px;
    }


    @keyframes colorCycle {
        0% {
            background-color: red;
        }

        33% {
            background-color: red;
        }

        66% {
            background-color: transparent;
        }

        100% {
            background-color: red;
        }
    }

    .hover_clear div:hover {
        font-size: 18px;
        width: 200px !important;
        background-color: black;
        text-shadow: unset !important;
        color: white !important;
        box-shadow: 3px 5px 5px #000000;
        padding: 3px 5px;
        user-select: none;
        -webkit-user-drag: none;
        -webkit-user-modify: read-only;
        fill: white !important;
    }

    .hover_clear div:hover svg text {
        fill: white !important;
    }

    text {}

    .visible_text {
        width: 100%;
        height: 100%;
        height: 100%;
        align-content: center;
    }
     div[id^=text] {
        mix-blend-mode: difference;
        filter: invert(1);
     }
</style>