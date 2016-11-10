/**
 * Created by Simon on 08/11/2016.
 */
import  swfobject from 'es-swfobject';
import  EsMessager from '../../../common/messenger';
class VideoPlay {
    constructor(elment) {
        this.dom = $(elment);
        this.data = this.dom.data();
        this.player = {};
    }


    play() {
        console.log(this.data.media);
        if (this.data.media.mediaSource == 'self') {
            this._playVideo();
        } else {
            this._playerSwf();
        }
    }

    _playerSwf() {
        console.log(this.dom, this.data);
        this.dom.html('<div id="lesson-swf-player"></div>');
        swfobject.embedSWF(this.data.media.mediaUri,
            'lesson-swf-player', '100%', '100%', "9.0.0", null, null, {
                wmode: 'opaque',
                allowFullScreen: 'true'
            });
    }

    _playVideo() {
        // if ((lesson.mediaConvertStatus == 'waiting') || (lesson.mediaConvertStatus == 'doing')) {
        //     Notify.warning('视频文件正在转换中，稍后完成后即可查看');
        //     return;
        // }
        let startTime = this.data.startTime | 0;
        let playerUrl = `/course/${this.data.courseId}/task/${this.data.taskId}/player`;// '../../course/' + lesson.courseId + '/lesson/' + lesson.id + '/player';
        if (startTime) {
            playerUrl += "?starttime=" + startTime;
        }
        const html = `<iframe src='${playerUrl}' name='viewerIframe' id='viewerIframe' width='100%' allowfullscreen webkitallowfullscreen height='100%' style='border:0px'></iframe>`;
        let self = this;
        this.dom.show();
        this.dom.html(html);

        var messenger = new EsMessager({
            name: 'parent',
            project: 'PlayerProject',
            children: [document.getElementById('viewerIframe')],
            type: 'parent'
        });

        messenger.on("ended", function () {
            console.log('messenger------------', 'ended')
            var player = self.player;
            player.playing = false;
            self.player = player;
            self._onFinishLearnLesson();
        });

        messenger.on("playing", function () {
            console.log('messenger------------', 'playing')
            var player = self.player;
            player.playing = true;
            self.player = player;
        });

        messenger.on("paused", function () {
            console.log('messenger------------', 'paused')
            var player = self.player;
            player.playing = false;
            self.player = player;
        });
    }

    _onFinishLearnLesson() {
        console.log('messenger------------', '_onFinishLearnLesson')
    }


}
let videoplay = new VideoPlay("#video-content");
videoplay.play();

//
// _videoPlay: function (lesson) {
//     var self = this;
//
//     if (lesson.mediaSource == 'self') {
//         var lessonVideoDiv = $('#lesson-video-content');
//
//         if ((lesson.mediaConvertStatus == 'waiting') || (lesson.mediaConvertStatus == 'doing')) {
//             Notify.warning('视频文件正在转换中，稍后完成后即可查看');
//             return;
//         }
//
//         var playerUrl = '../../course/' + lesson.courseId + '/lesson/' + lesson.id + '/player';
//         if (self.get('starttime')) {
//             playerUrl += "?starttime=" + self.get('starttime');
//         }
//         var html = '<iframe src=\'' + playerUrl + '\' name=\'viewerIframe\' id=\'viewerIframe\' width=\'100%\'allowfullscreen webkitallowfullscreen height=\'100%\' style=\'border:0px\'></iframe>';
//
//         $("#lesson-video-content").show();
//         $("#lesson-video-content").html(html);
//
//         var messenger = new Messenger({
//             name: 'parent',
//             project: 'PlayerProject',
//             children: [document.getElementById('viewerIframe')],
//             type: 'parent'
//         });
//
//         messenger.on("ended", function () {
//             var player = self.get("player");
//             player.playing = false;
//             self.set("player", player);
//             self._onFinishLearnLesson();
//         });
//
//         messenger.on("playing", function () {
//             var player = self.get("player");
//             player.playing = true;
//             self.set("player", player);
//         });
//
//         messenger.on("paused", function () {
//             var player = self.get("player");
//             player.playing = false;
//             self.set("player", player);
//         });
//
//         self.set("player", {});
//     } else {
//         $("#lesson-swf-content").html('<div id="lesson-swf-player"></div>');
//         swfobject.embedSWF(lesson.mediaUri,
//             'lesson-swf-player', '100%', '100%', "9.0.0", null, null, {
//                 wmode: 'opaque',
//                 allowFullScreen: 'true'
//             });
//         $("#lesson-swf-content").show();
//     }
// }
// ,