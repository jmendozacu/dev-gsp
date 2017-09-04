/* global document, jQuery */

(function(context, $){

    var nextCheckCookie = 'FmNextCheck';
    var latestVersionCookie = 'FmLastVersion';
    var downloadLinkCookie = 'FmLastDownload';
    var checkInterval = 3600; // 1 hour in seconds
    var failInterval = 600; // 10 minutes in seconds

    function getCookie(cookieName) {
        var value = '; ' + context.document.cookie;
        var parts = value.split('; ' + cookieName + '=');
        if (parts.length == 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }
        return null;
    }

    function setCookie(cookieName, cookieVaue) {
        context.document.cookie = cookieName + '=' + encodeURIComponent(cookieVaue) +
            '; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/';
    }

    function getCurrentTimestamp() {
        return Math.floor(Date.now() / 1000);
    }

    function mustCheck(nextCheckTimestam, currentTimestamp) {
        return currentTimestamp >= nextCheckTimestam;
    }

    function getLatestRelease(succcessCallback, failCallback) {
        //var url = 'https://api.github.com/repos/' + context.fmRepositoryPath + 'releases/latest';
        var url = 'http://developers.fyndiq.com/repos/' + context.fmRepositoryPath + 'releases/latest.json?t=' + Date.now();
        succcessCallback = succcessCallback || function(){};
        failCallback = failCallback || function(){};
        return $.getJSON(url).done(succcessCallback).fail(failCallback);
    }

    function getVersion(versionStr) {
        var re = /(\d+)\.(\d+)\.(\d+)/i;
        var matches = versionStr.match(re);
        if (matches) {
            return Array(9 - matches[1].length).join(0) + matches[1] +
                Array(9 - matches[1].length).join(0) + matches[2] +
                Array(9 - matches[3].length).join(0) + matches[3];
        }
        return '';
    }

    function isNewRelease(moduleVersion, gitHubRelease) {
        if (!gitHubRelease){
            return false;
        }
        return getVersion(gitHubRelease) > getVersion(moduleVersion);
    }

    function getCurrentRelease() {
        return context.fmModuleVersion || '0.0.0';
    }

    function showReleaseNotification(releaseData, moduleVersion) {
        context.FmGui.showUpdateMessage(releaseData.tag_name, moduleVersion, releaseData.html_url);
    }

    function init(context) {
        // Check if update check is disabled
        if (context.hasOwnProperty('fmDisableUpdateCheck') && context.fmDisableUpdateCheck) {
            return;
        }
        $(context.document).ready(function() {
            var moduleVersion = getCurrentRelease();
            var latestVersion = getCookie(latestVersionCookie);
            if (mustCheck(getCookie(nextCheckCookie), getCurrentTimestamp())) {
                // Show throbber
                context.FmGui.showCheckUpdate();
                getLatestRelease(function(latestReleaseData){
                    if (latestReleaseData.hasOwnProperty('tag_name') &&
                        isNewRelease(moduleVersion, latestReleaseData.tag_name)
                    ) {
                        // Save the new version for no-check-reuse
                        setCookie(latestVersionCookie, latestReleaseData.tag_name);
                        setCookie(downloadLinkCookie, latestReleaseData.html_url);
                        showReleaseNotification(latestReleaseData, moduleVersion);
                    }
                    // Set the next check after 1 hour
                    setCookie(nextCheckCookie, getCurrentTimestamp() + checkInterval);
                    // Hide throbber
                    context.FmGui.hideCheckUpdate();
                }, function(){
                    // Hide throbber on failure
                    // Set the next check after 1 hour
                    setCookie(nextCheckCookie, getCurrentTimestamp() + failInterval);
                    context.FmGui.hideCheckUpdate();
                });
                return false;
            }

            if (isNewRelease(moduleVersion, latestVersion)) {
                showReleaseNotification({
                    tag_name: latestVersion,
                    html_url: getCookie(downloadLinkCookie)
                }, moduleVersion);
            }
        });
    }

    init(context);

}(window, jQuery));
