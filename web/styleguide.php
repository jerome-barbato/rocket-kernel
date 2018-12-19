<html>
<head>
	<title>Style Guide</title>
	<link rel="stylesheet" media="screen" href="static/css/screen.css">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
	<script type="text/javascript" src="static/js/browser.js"></script>
	<script src="https://cdn.jsdelivr.net/clipboard.js/1.6.0/clipboard.min.js"></script>
	<style type="text/css">
		body{ background: #f4f5f8; color: #333; font-size: 18px; padding-left: 200px; font-family: 'Open Sans', sans-serif }
		.styleguide-title{ font-size: 42px; margin-top:2em; margin-bottom: 1em; color: #7c939d; padding-top: 10px }
		.styleguide-subtitle{ font-size: 25px; margin-top: 2em; margin-bottom: 1em }
		.styleguide-font{ font-size: 24px; text-transform: uppercase }
		.styleguide-title+.styleguide-subtitle{ margin-top: 20px }
		.styleguide-section{ max-width: 760px; margin: auto; width: 90%; clear: both; margin-bottom: 50px }
		.styleguide-color{
			display: inline-block; float: left; margin: 10px; background-color: white; border-radius: 3px;
			box-shadow: 0 2px 0 #dfdfdf;
		}
		.styleguide-color small,.styleguide-color b{ display: block; margin: 5px 10px }
		.styleguide-color small{ font-size: 12px; color: #999 }
		.styleguide-color b{ font-size: 14px; color: #666; text-transform: uppercase }
		.styleguide-color span{
			display: block; width: 105px; height: 95px; border-radius: 3px 3px 0 0;
		}
		.styleguide-fonts{ background: #fff; padding: 30px; border-radius: 3px }
		.styleguide-fonts > div+div{ margin-top: 50px }
		.styleguide-font-variant{ margin-top: 10px  }
		.styleguide-typography .text{ display: block; margin: 10px 0; padding: 10px 0; border-bottom: 1px solid #eee; position: relative; line-height: 100% }
		.styleguide-button .button{ margin: 10px 0 }
		.styleguide-button .button+.button{ margin-left: 10px }
		.styleguide-icon a{ padding: 10px; display: inline-block; float: left; font-size: 13px; width: 33.33%; border-radius:3px }
		.styleguide-icon a:hover{ background: #e4e6ee }
		.styleguide-icon a:before{ font-size: 22px; vertical-align: middle; margin-right: 15px }
		.styleguide-breakpoint td{ font-size: 16px; margin: 5px }
		.styleguide-copied{
			position: fixed; left: 0; width: 100%; text-align: center; background: #fff; z-index: 9999;
			padding: 20px 0; display: none; font-size: 12px; top: 50%; margin-top: -26px; box-shadow: 0 0 5px rgba(0,0,0,0.2);
		}
		[data-clipboard-text]{ cursor: pointer }
		.styleguide-nav{
			position: fixed; left: 0; top:0; height: 100%; width: 200px;
			border-left: 4px solid #cdcfdf; background-color: #eceef3;
		}
		.styleguide-nav a{ color: #95a5a6; font-size: 14px; padding: 10px; display: block }
		.styleguide-nav a:hover{ background-color: #e4e6ee; color: #121e21 }
		.styleguide-nav div{
			background: linear-gradient(45deg, rgba(20,109,109,1) 0%, rgba(74,104,66,1) 100%);
			height: 180px; line-height: 180px; color: #fff; text-align: center; font-size: 15px;
			margin-bottom: 40px;
		}
		.styleguide-info{ font-size: 11px; margin-top: 20px; display: inline-block; background: #eee; padding: 5px 8px; border-radius: 3px; }
	</style>
</head>
<body>

<div class="styleguide-nav">
	<div>Style Guide</div>
	<a href="#colors">Colors</a>
	<a href="#typography">Typography</a>
	<a href="#buttons">Buttons</a>
	<a href="#icons">Icons</a>
</div>

<div id="colors" class="styleguide-section styleguide-colors clearfix">
	<div class="styleguide-title">Colors</div>
		<?php foreach ($this->data['scss']['colors'] as $name=>$color) :?>
			<a class="styleguide-color">
				<span style="background: <?=$color?>" data-clipboard-text="$<?=$name?>"></span>
				<small><?=$name?></small>
				<b><?=$color?></b>
			</a>
		<?php endforeach; ?>
</div>

<div id="typography" class="styleguide-section styleguide-typography">
	<div class="styleguide-title">Typography</div>
	<div class="styleguide-subtitle">Font families</div>

	<div class="styleguide-fonts">
			<?php foreach ($this->data['scss']['fonts'] as $font=>$variants) :?>
				<div style="font-family: <?=$font?>">
					<div data-clipboard-text="@include font('<?=$font?>')" class="styleguide-font"><?=$font?></div>
						<?php foreach ($variants as $variant):?>
								<?php if($variant['style']!='italic'): ?><a class="styleguide-info"><?=$variant['variant']?> ( <?=$variant['weight']?> )</a><?php endif ?>
							<div class="styleguide-font-variant" style="font-weight: <?=$variant['weight']?>; font-style: <?=$variant['style']?>; font-stretch: <?=$variant['stretch']?>" title="<?=$variant['weight']?> <?=$variant['style']?> <?=$variant['stretch']?>" data-clipboard-text="@include font('<?=$font?>'); font-weight:<?=$variant['weight']?>; font-style:<?=$variant['style']?>">The old brown fox jumped over the lazy dog</div>
						<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
	</div>

	<div class="styleguide-subtitle">Text sizes</div>
		<?php foreach ($this->data['scss']['text'] as $name) :?>
			<div class="text text--<?=$name?>" title="text--<?=$name?>" data-clipboard-text="@include text(<?=$name?>)">Lorem ipsum</div>
		<?php endforeach; ?>
</div>

<div id="buttons" class="styleguide-section styleguide-button">
	<div class="styleguide-title">Buttons</div>
		<?php foreach ($this->data['scss']['button'] as $name) :?>
			<a class="button button--<?=$name?>" title="button--<?=$name?>" data-clipboard-text='button="<?=$name?>"'>button</a>
		<?php endforeach; ?>
</div>

<div id="icons" class="styleguide-section styleguide-icon clearfix">
	<div class="styleguide-title">Icons</div>
		<?php foreach ($this->data['scss']['icons'] as $name) :?>
			<a data-icon="<?=$name?>" data-clipboard-text="@include icon('<?=$name?>')"><?=$name?></a>
		<?php endforeach; ?>
</div>

<span class="styleguide-copied">Copied to clipboard</span>

<script type="text/javascript" src="static/js/vendor.js"></script>
<script type="text/javascript">

		var app = {debug:false};

		clipboard = new Clipboard('[data-clipboard-text]');
		clipboard.on('success', function(e) {
				$('.styleguide-copied').show();
				setTimeout(function(){ $('.styleguide-copied').fadeOut(400)}, 700);
		});

		$('.text').each(function(){
				$(this).prepend($(this).css('fontSize')+' ');
		});
</script>
</body>
</html>
