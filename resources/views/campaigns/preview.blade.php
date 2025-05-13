<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>{{ $campaign->getSubject() }}</title>

	<style>

		*, ::after, ::before {
		box-sizing: border-box;
		}

		svg{
			vertical-align: middle;
		}

		@if ($campaign->type != 'plain-text')
			body {
				padding-top: 45px !important;
			}
		@endif
		/* Style the tab */
		div.tab {
			overflow: hidden;
			border: 1px solid #ccc;
			background-color: #f1f1f1;
			@if ($campaign->type != 'plain-text')
				position: fixed;
				top: 0;
				width: 100%;
				left: 0;
			@endif
			z-index: 2;
		}

		/* Style the buttons inside the tab */
		div.tab button {
			background-color: inherit;
			float: left;
			border: none;
			outline: none;
			cursor: pointer;
			padding:23px 16px;
			transition: 0.3s;
		}

		/* Change background color of buttons on hover */
		div.tab button:hover {
			background-color: #ddd;
		}

		/* Create an active/current tablink class */
		div.tab button.active {
			background-color: #ccc;
		}

		/* Style the tab content */
		.tabcontent {
			display: none;
			border: 1px solid #ccc;
			border-top: none;
			border-bottom: none;
		}
		.email_preview_frame {
			margin: 0;
			border: none;
			width: 100%;
			height: calc(100vh - 50px);
		}
	</style>

	<style>
		.ms-auto{
			float:right;
			margin-right:20px;
			margin-top:10px;
		}
		.social-buttons {
			margin:0px;
			list-style-type: none;
			box-sizing: border-box;
			*zoom: 1;
		}
		.social-buttons:before,
		.social-buttons:after {
		box-sizing: border-box;
		}
		.social-buttons:before,
		.social-buttons:after {
		content: " ";
		display: table;
		line-height: 0;
		}
		.social-buttons:after {
		clear: both;
		}
		.button__share {
		float: left;
		background-color: #888;
		margin-right: .7em;
		margin-bottom: .7em;
		border-radius: 4px;
		}
		.button__share:last-child {
		margin-right: 0;
		}
		.button__share:hover {
		-moz-opacity: 0.8;
		-khtml-opacity: 0.8;
		-webkit-opacity: 0.8;
		opacity: 0.8;
		-ms-filter: progid:DXImageTransform.Microsoft.Alpha(opacity=80);
		filter: alpha(opacity=80);
		}
		.button__share a {
		color: #fff;
		font-family: Arial, Helvetica, -apple-system, sans-serif;
		font-style: normal;
		line-height: 1.33;
		text-decoration: none;
		padding: .2em 1.2em;
		display: inline-block;
		height: 40px;
		display: flex;
		align-items: center;
		}
		.button__share--facebook {
		background-color: #3b5998;
		}
		.button__share--googleplus {
		background-color: #dc4e41;
		}
		.button__share--twitter {
		background-color: #55acee;
		}
		.button__share--linkedin {
		background-color: #0077b5;
		}
		.button__share--reddit {
		background-color: #ff4500;
		}
		.button__share--hackernews {
		background-color: #ff6600;
		}
		.button__share--buffer {
		background-color: #323b43;
		}
		.button__share--digg {
		background-color: #000000;
		}
		.button__share--tumblr {
		background-color: #35465c;
		}
		.button__share--stumbleupon {
		background-color: #eb4924;
		}
		.button__share--delicious {
		background-color: #3399ff;
		}
		.button__share--evernote {
		background-color: #7ac142;
		}
		.button__share--wordpress {
		background-color: #21759b;
		}
		.button__share--pocket {
		background-color: #ef4056;
		}
		.button__share--pinterest {
		background-color: #bd081c;
		}
	</style>

</head>

<body style="margin:0;padding:0;">
	@if ($campaign->type != 'plain-text')
		<div class="tab">
		  <button class="tablinks active" onclick="openTab(event, 'html')">{{ trans('messages.web_view_html_tab') }}</button>
		  <button class="tablinks" onclick="openTab(event, 'plain')">{{ trans('messages.web_view_plain_tab') }}</button>

			<div class="ms-auto">
				<ul class="social-buttons">
					<li class="button__share button__share--facebook">
						<a href="javascript:void(window.open('https://www.facebook.com/sharer.php?u=' + encodeURIComponent('{{action('Pub\CampaignController@previewContent', [ 'uid' => $campaign->uid, 'customer_uid' => $campaign->customer->uid ])}}') + '?t=' + encodeURIComponent(document.title),'_blank'))">
							<span class="d-flex align-items-center">
								<span class="me-2">
									<svg style="height:20px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 404.1 800"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path d="M12.9,426.1h87.7v361A12.9,12.9,0,0,0,113.5,800H262.2a12.9,12.9,0,0,0,12.9-12.9V427.8H375.9a12.8,12.8,0,0,0,12.8-11.4l15.3-133a12.7,12.7,0,0,0-3.2-10,12.5,12.5,0,0,0-9.6-4.3H275.1V185.7c0-25.1,13.5-37.8,40.2-37.8h75.9A12.9,12.9,0,0,0,404.1,135V13A12.9,12.9,0,0,0,391.2.1H281.8c-18.2,0-81.3,3.6-131.1,49.4C95.5,100.2,103.1,161.1,105,171.6v97.5H12.9A12.9,12.9,0,0,0,0,282V413.2A12.9,12.9,0,0,0,12.9,426.1Z" style="fill:#fff"/></g></g></svg>
								</span>
								<span>Facebook</span>
							</span>
						</a>
					</li>
					<li class="button__share button__share--twitter">
						<a href="javascript:void(window.open('https://twitter.com/share?url=' + encodeURIComponent('{{action('Pub\CampaignController@previewContent', [ 'uid' => $campaign->uid, 'customer_uid' => $campaign->customer->uid ])}}') + '&amp;text=' + encodeURIComponent(document.title) + '&amp;via=fabienb&amp;hashtags=koandesign','_blank'))">
							<span class="d-flex align-items-center">
								<span class="me-2">
									<svg style="height:20px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 246.2 200"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path d="M221,49.8c.1,2.2.1,4.3.1,6.5,0,66.8-50.8,143.7-143.7,143.7h0A142.8,142.8,0,0,1,0,177.3a96.7,96.7,0,0,0,12,.8,100.9,100.9,0,0,0,62.7-21.7,50.4,50.4,0,0,1-47.1-35.1,50.3,50.3,0,0,0,22.8-.8A50.5,50.5,0,0,1,9.9,71v-.7a49.4,49.4,0,0,0,22.9,6.3A50.5,50.5,0,0,1,17.1,9.2,143.6,143.6,0,0,0,121.2,62a50.6,50.6,0,0,1,86.1-46.1A103.4,103.4,0,0,0,239.4,3.7a51.2,51.2,0,0,1-22.2,27.9,101.7,101.7,0,0,0,29-8A104.6,104.6,0,0,1,221,49.8Z" style="fill:#fff"/></g></g></svg>
								</span>
								<span>Twitter</span>
							</span>
						</a>
					</li>
					<!-- optional Twitter username of content author (don’t include “@”)
					optional Hashtags appended onto the tweet (comma separated. don’t include “#”) -->
					<li class="button__share button__share--linkedin">
						<a href="javascript:void(window.open('https://www.linkedin.com/shareArticle?url=' + encodeURIComponent('{{action('Pub\CampaignController@previewContent', [ 'uid' => $campaign->uid, 'customer_uid' => $campaign->customer->uid ])}}') + '&amp;title=' + encodeURIComponent(document.title),'_blank'))">
							<span class="d-flex align-items-center">
								<span class="me-2">
									<svg style="height:20px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 53.4 51"><g id="Layer_2" data-name="Layer 2"><g id="svg3070"><path id="path28" d="M12.1,51V16.6H.7V51ZM6.4,11.9c4,0,6.5-2.6,6.5-6S10.4,0,6.5,0,0,2.6,0,5.9s2.5,6,6.3,6Z" style="fill:#fff;fill-rule:evenodd"/><path id="path30" d="M18.5,51H29.9V31.8a7.9,7.9,0,0,1,.4-2.8,6.3,6.3,0,0,1,5.8-4.2c4.2,0,5.8,3.2,5.8,7.8V51H53.4V31.3c0-10.6-5.7-15.5-13.2-15.5a11.3,11.3,0,0,0-10.4,5.8h.1v-5H18.5c.1,3.2,0,34.4,0,34.4Z" style="fill:#fff;fill-rule:evenodd"/></g></g></svg>
								</span>
								<span>Linkedin</span>
							</span>
						</a>
					</li>
				</ul>
			</div>          
	
		</div>

		<div id="html" class="tabcontent" style="display: block">
			<iframe class="email_preview_frame" src="{{ action('CampaignController@previewContent', $campaign->uid) }}"></iframe>
		</div>
		
	@endif

	@if ($campaign->type != 'plain-text')
		<div id="plain" class="tabcontent"  style="padding: 10px">
	@endif
		<pre style="padding-left:20px">{!! $campaign->getPlainContent() !!}</pre>
	@if ($campaign->type != 'plain-text')
		</div>
	@endif

	<script>
	function openTab(evt, cityName) {
		// Declare all variables
		var i, tabcontent, tablinks;

		// Get all elements with class="tabcontent" and hide them
		tabcontent = document.getElementsByClassName("tabcontent");
		for (i = 0; i < tabcontent.length; i++) {
			tabcontent[i].style.display = "none";
		}

		// Get all elements with class="tablinks" and remove the class "active"
		tablinks = document.getElementsByClassName("tablinks");
		for (i = 0; i < tablinks.length; i++) {
			tablinks[i].className = tablinks[i].className.replace(" active", "");
		}

		// Show the current tab, and add an "active" class to the button that opened the tab
		document.getElementById(cityName).style.display = "block";
		evt.currentTarget.className += " active";
	}
	</script>
</body>

</html>

