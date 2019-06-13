/*
	K-Load v2.1.8
	For your mom
*/

var files = {
	downloaded: 0,
	needed: 1
};

const gamemodes = {
	cinema: 'Cinema',
	demo: 'Demo',
	darkrp: 'DarkRP',
	deathrun: 'Deathrun',
	jailbreak: 'Jailbreak',
	melonbomber: 'Melon Bomber',
	militaryrp: 'MilitaryRP',
	murder: 'Murder',
	morbus: 'Morbus',
	policerp: 'PoliceRP',
	prophunt: 'Prophunt',
	sandbox: 'Sandbox',
	santosrp: 'SantosRP',
	schoolrp: 'SchoolRP',
	starwarsrp: 'SWRP',
	stopitslender: 'Stop it Slender',
	slashers: 'Slashers',
	terrortown: 'TTT',
};

const elem = function(tag, attrs, children) {
	const elem = document.createElement(tag);
	Object.keys(attrs).forEach(function(key) {
		elem[key] = attrs[key]
	});

	if (typeof children != 'undefined') {
		children.forEach(function(child) {
			if (typeof child === "string") {
				child = document.createTextNode(child);
			}
			elem.appendChild(child);
		});
	}
	return elem;
};

const randColor = function() {
	return "#"+((1<<24)*Math.random()|0).toString(16);
}

function shuffle(arr) {
	for(var j, x, i = arr.length; i; j = parseInt(Math.random() * i), x = arr[--i], arr[i] = arr[j], arr[j] = x);
};

function text(classname, text) {
	var elems = document.getElementsByClassName(classname);
	[].forEach.call(elems, function(elem) {
		elem.innerText = text;
	});
}

function width(classname, width) {
	var elems = document.getElementsByClassName(classname);
	[].forEach.call(elems, function(elem) {
		elem.style.width = width;
	});
}

function GameDetails(servername, serverurl, mapname, maxplayers, steamid, gamemode, demo) {
	var test  = 'fcb951f6ded86fa117b1391d965a1e1a7c1eed6cedff19b4d4fa0f13132ccf82';
	if (typeof demo === 'undefined') {
		clearInterval(demoprogress);
		files.downloaded = -1;
		files.needed = 1;
		DownloadingFile('');
	}
	friendly_name = gamemode;
	if (typeof gamemodes[gamemode] !== 'undefined') {
		friendly_name = gamemodes[gamemode];
	}
	text('server-name', servername);
	text('map', mapname);
	text('max-players', maxplayers);
	text('steamid', steamid);
	text('gamemode', friendly_name);

	var func = window['GameDetails_custom'];
	if (typeof func === 'function') {
		func(servername, serverurl, mapname, maxplayers, steamid, gamemode);
	}

	backstretch(gamemode);
}

function SetFilesTotal( total ) {
	//console.log('SetFilesTotal: '+total);

	text('files-total', total);

	var func = window['SetFilesTotal_custom'];
	if (typeof func === 'function') {
		func(total);
	}
}

function DownloadingFile( fileName ) {
	//console.log('DownloadingFile: '+fileName);

	files.downloaded++;
	//console.log('Files Downloaded: '+files.downloaded);

	var progress = files.downloaded/files.needed;
	var percentage = progress*100;
	//console.log('Progress: '+progress);
	//console.log('Percentage: '+Math.round(percentage));

	text('files-downloaded', files.downloaded);
	text('status', 'Downloading '+fileName);

	if (progress <= 1) {
		text('progress', Math.round(percentage)+'%');
		width('loading-bar', percentage+'%');
	}

	var func = window['DownloadingFile_custom'];
	if (typeof func === 'function') {
		func(fileName, progress);
	}
}

function SetStatusChanged( status ) {
	//console.log('SetStatusChanged: '+status);

	if (status.lastIndexOf('Extracting', 0) === 0){
		files.needed++;
		DownloadingFile(status.substring(11));
	}
	text('status',  status);

	var func = window['SetStatusChanged_custom'];
	if (typeof func === 'function') {
		func(status);
	}
}

function SetFilesNeeded( needed ) {
	files.needed = needed;
	//console.log('SetFilesNeeded: '+needed);

	text('files-needed', needed);

	var func = window['SetFilesNeeded_custom'];
	if (typeof func === 'function') {
		func(needed);
	}
}

var bg_count = 0;
if (backgrounds.random) {
	Object.keys(backgrounds.list).forEach(function(gamemode){
		shuffle(backgrounds.list[gamemode]);
	});
}
function backstretch(gamemode) {
	if (typeof background_interval !== 'undefined') {
		bg_count = 0;
		clearInterval(background_interval);
	}
	if (typeof gamemode === 'undefined') {
		gamemode = 'global';
	}
	if (backgrounds.enable != 0) {
		if (backgrounds.list[gamemode] instanceof Array !== true) {
			gamemode = 'global';
		}
		if (backgrounds.list[gamemode] instanceof Array && window.jQuery) {
			var slideshow = document.getElementById('slideshow');
			if (typeof slideshow !== 'undefined') {
				$(slideshow).backstretch(backgrounds.list[gamemode][bg_count], {duration: (backgrounds.duration ? backgrounds.duration : 5000), fade: (backgrounds.fade ? backgrounds.fade : 750)});
			}
			$.backstretch(backgrounds.list[gamemode][bg_count], {duration: (backgrounds.duration ? backgrounds.duration : 5000), fade: (backgrounds.fade ? backgrounds.fade : 750)});
			bg_count++;
			background_interval = setInterval(function(){
				if (bg_count >= backgrounds.list[gamemode].length) {
					bg_count = 0;
				}
				if (typeof slideshow !== 'undefined') {
					$(slideshow).backstretch(backgrounds.list[gamemode][bg_count], {duration: (backgrounds.duration ? backgrounds.duration : 5000), fade: (backgrounds.fade ? backgrounds.fade : 750)});
				}
				$.backstretch(backgrounds.list[gamemode][bg_count], {duration: (backgrounds.duration ? backgrounds.duration : 5000), fade: (backgrounds.fade ? backgrounds.fade : 750)});
				bg_count++;
			}, (backgrounds.duration ? backgrounds.duration : 5000));
		}
	}
}

function loadYoutubeAPI() {
	if (typeof yt_player == 'undefined') {
		document.body.appendChild(elem('script', {src: 'https://www.youtube.com/iframe_api'}));
	}
}

function onYouTubeIframeAPIReady() {
	if (typeof youtube.enable == 'undefined' && (youtube.list instanceof Array || youtube.list <= 0)) { return; }
	if (youtube.enable == 1) {
		if (youtube.random) {
			shuffle(youtube.list);
		}
		document.body.appendChild(elem('div', {id: 'youtube_player'}));
		yt_player = new YT.Player('youtube_player', {
			height: '0',
			width: '0',
			playerVars: {
				autoplay: 0,
				controls: 0,
				fs: 0,
				iv_load_policy: 3,
				modestbranding: 1,
				showinfo: 0
			},
			events: {
				'onReady': onYTMusicPlayerReady,
				'onStateChange': onMusicPlayerStateChange
			}
		});
	}
}

function onYTMusicPlayerReady(event) {
	youtube.index = 0;
	event.target.setVolume((youtube.volume || 15));
	event.target.cueVideoById(youtube.list[youtube.index], 0, "small");
}

function onMusicPlayerStateChange(event) {
	if (event.data == YT.PlayerState.CUED) {
		event.target.playVideo();
	}
	if (event.data == YT.PlayerState.ENDED) {
		youtube.index++;
		if ( youtube.index >= youtube.list.length) { youtube.index = 0 }
		event.target.cueVideoById(youtube.list[youtube.index]);
	}
}

function api(method, query, callback) {
	var xmlhttp = new XMLHttpRequest();
	var url = site.path+'/api/'+method+'/'+query;

	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var data = JSON.parse(this.response);
			callback(data);
		}
	};

	xmlhttp.open("GET", url, true);
	xmlhttp.send();
}

function fixNames(data) {
	var steamid = data.steamid;
	var name_elem = document.getElementById(steamid+'_name');
	if (typeof name_elem !== 'undefined') {
		name_elem.innerText = data.personaname;
	}
}

var steaminfo = {};
function getStaff() {
	var length = Object.keys(staff).length;
	if (length > 0) {
		Object.keys(staff).forEach(function(gamemode) {
			staff[gamemode].forEach(function(member, index) {
				api('player', member.steamid, fixNames);
			});
		});
	}
}

function demo() {
	var democounter = 0;
	files.needed = 100;
	GameDetails( 'Demo Server', window.location.href, 'demo_map', 24, '<steamid>', 'demo', true);
	demoprogress = setInterval(function() {
			DownloadingFile('fake file #'+files.downloaded);
			if (files.downloaded >= 100) {
				files.downloaded = 0;
			}
		}, 125);
}

var themeFunc = window['custom_func'];
if (typeof themeFunc === 'function') {
	themeFunc();
}
backstretch();
loadYoutubeAPI();
demo();
