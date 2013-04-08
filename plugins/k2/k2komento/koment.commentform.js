Komento.module("komento.commentform", function(e) {
        var t = this, n = Komento.require(), r = [];
        r.push("komento.common"), r.push("komento.upload"), Komento.options.config.enable_bbcode == 1 && (r.push("markitup"), r.push("komento.bbcode")), Komento.options.config.show_location == 1 && r.push("location"), n.script.apply(n, r).image(Komento.options.spinner).done(function() {
                Komento.Controller("CommentForm", {defaults: {"{addCommentButton}": ".addCommentButton", "{formArea}": ".formArea", "{commentInput}": "#commentInput", "{markItUpButtons}": ".markItUpButton a", "{submitButton}": ".submitButton", "{parentId}": 'input[name="parent"]', "{usernameInput}": "#register-username", "{nameInput}": "#register-name", "{emailInput}": "#register-email", "{websiteInput}": "#register-website", "{registerCheckbox}": "#register-checkbox", "{tncCheckbox}": ".tncCheckbox", "{tncRead}": ".tncRead", "{subscribeForm}": ".subscribeForm", "{subscribeCheckbox}": ".subscribeCheckbox", "{unsubscribeButton}": ".unsubscribeButton", "{locationForm}": ".locationForm", "{formAlert}": ".formAlert", "{recaptchaChallenge}": "#recaptcha_challenge_field", "{recaptchaResponse}": "#recaptcha_response_field", "{captchaImage}": "#captcha-image", "{captchaResponse}": "#captcha-response", "{recaptchaResponse}":"#recaptcha_response_field", "{captchaId}": "#captcha-id", "{captchaReload}": ".kmt-captcha-reload", "{locationInput}": ".locationInput", "{locationLatitude}": ".locationLatitude", "{locationLongitude}": ".locationLongitude", "{parentLink}": ".parentLink", "{parentContainer}": ".parentContainer", "{cancelStaticReplyButton}": ".cancelStaticReply", "{commentLength}": ".commentLength", "{commentLengthCount}": ".commentLengthCount", "{uploaderWrap}": ".uploaderWrap", "{pageItemId}": ".pageItemId"}}, function(t) {
                        return{init: function() {
                                        t.parentid = 0, t.commentInput().val(""), Komento.options.config.enable_bbcode == 1 && t.commentInput().markItUp(e.getBBcodeSettings()), Komento.options.config.show_location == 1 && t.locationForm().exists() && t.locationForm().implement("Komento.Controller.Location.Form.Simple"), Komento.options.config.upload_enable == 1 && Komento.options.acl.upload_attachment == 1 && t.uploaderWrap().exists() && (Komento.options.element.commentupload = new Komento.Controller.UploadForm(e(".uploaderWrap")), Komento.options.element.commentupload.kmt = Komento.options.element)
                                }, "{commentInput} textChange": function(e) {
                                        t.commentLengthCheck(), t.experimentalValidateComment()
                                }, "{commentInput} keyup": function(e) {
                                        t.commentLengthCheck()
                                }, "[{nameInput}, {emailInput}, {websiteInput}, {commentInput}] keyup": function() {
                                        t.experimentalValidateComment()
                                }, "[{subscribeCheckbox}, {tncCheckbox}] click": function() {
                                        t.experimentalValidateComment()
                                }, "{tncRead} click": function() {
                                        e.dialog({title: e.language("COM_KOMENTO_FORM_TNC"), customClass: "kmt-dialog", width: 500, showOverlay: !1, content: Komento.options.config.tnc_text.replace(/\n/g, "<br />")})
                                }, "{submitButton} click": function(e) {
                                        e.checkClick() && e.html('<img src="' + Komento.options.spinner + '" />'), t.validateComment()
                                }, "{unsubscribeButton} click": function(e) {
                                        e.checkClick() && (e.html('<img src="' + Komento.options.spinner + '" />'), t.unsubscribe())
                                }, "{captchaReload} click": function() {
                                        t.reloadCaptcha()
                                }, "{parentLink} mouseover": function() {
                                        t.parentContainer().show()
                                }, "{parentLink} mouseout": function() {
                                        t.parentContainer().hide()
                                }, "{cancelStaticReplyButton} click": function() {
                                        t.cancelStaticReply()
                                }, "{addCommentButton} click": function() {
                                        t.loadForm()
                                }, loadForm: function() {
                                        Komento.options.config.form_toggle_button == 1 && (t.addCommentButton().hide(), t.formArea().show(), Komento.options.config.enable_upload == 1 && t.kmt.commentupload && t.kmt.commentupload.plupload.refresh())
                                }, hideForm: function() {
                                        Komento.options.config.form_toggle_button == 1 && (t.addCommentButton().show(), t.formArea().hide())
                                }, commentLengthCheck: function() {
                                        Komento.options.config.antispam_max_length_enable == 1 && t.commentInput().val().length > Komento.options.config.antispam_max_length && t.commentInput().val(t.commentInput().val().slice(0, Komento.options.config.antispam_max_length)), t.commentLengthCount().text(t.commentInput().val().length)
                                }, validateComment: function() {
                                        t.clearNotification();
                                        if (Komento.options.konfig.enable_js_form_validation == 0) {
                                                t.postComment();
                                                return
                                        }
                                        var n = e.trim(t.nameInput().val()), r = e.trim(t.emailInput().val()), i = e.trim(t.websiteInput().val()), s = e.trim(t.commentInput().val()), o = e.trim(t.captchaResponse().val()), u = e.trim(t.recaptchaResponse().val()), a = [];
                                        s.length == 0 ? (t.errorNotification(e.language("COM_KOMENTO_FORM_NOTIFICATION_COMMENT_REQUIRED")), a.push("1")) : Komento.options.config.antispam_min_length_enable == 1 && Komento.options.config.antispam_min_length > 0 && s.length < Komento.options.config.antispam_min_length && (t.errorNotification(e.language("COM_KOMENTO_FORM_NOTIFICATION_COMMENT_TOO_SHORT")), a.push("1"));
                                        if (t.captchaResponse().exists() && o.length == 0 || t.recaptchaResponse().exists() && u.length == 0)
                                                t.errorNotification(e.language("COM_KOMENTO_FORM_NOTIFICATION_CAPTCHA_REQUIRED")), a.push("1");
                                        n.length == 0 && (Komento.options.config.show_name == 2 && Komento.options.config.require_name == 2 || Komento.options.guest == 1 && Komento.options.config.show_name > 0 && Komento.options.config.require_name == 1) ? (t.nameInput().siblings("span").length == 0 ? t.nameInput().after('<span class="help-inline">' + e.language("COM_KOMENTO_FORM_NOTIFICATION_NAME_REQUIRED") + "</span>") : t.nameInput().siblings("span").show(), t.nameInput().parents("li").addClass("error"), a.push("1")) : (t.nameInput().siblings("span").hide(), t.nameInput().parents("li").removeClass("error")), t.emailInput().siblings("span").remove(), r.length == 0 && (Komento.options.config.show_email == 2 && (Komento.options.config.require_email == 2 || t.subscribeCheckbox().prop("checked")) || Komento.options.guest == 1 && Komento.options.config.show_email > 0 && (Komento.options.config.require_email == 1 || t.subscribeCheckbox().prop("checked"))) ? (t.emailInput().after('<span class="help-inline">' + e.language("COM_KOMENTO_FORM_NOTIFICATION_EMAIL_REQUIRED") + "</span>"), t.emailInput().parents("li").addClass("error"), a.push("1")) : r.length > 0 && !t.validateEmail(r) ? (t.emailInput().after('<span class="help-inline">' + e.language("COM_KOMENTO_FORM_NOTIFICATION_EMAIL_INVALID") + "</span>"), t.emailInput().parents("li").addClass("error"), a.push("1")) : t.emailInput().parents("li").removeClass("error"), t.websiteInput().siblings("span").remove(), i.length == 0 && (Komento.options.config.show_website == 2 && Komento.options.config.require_website == 2 || Komento.options.guest == 1 && Komento.options.config.show_website > 0 && Komento.options.config.require_website == 1) ? (t.websiteInput().after('<span class="help-inline">' + e.language("COM_KOMENTO_FORM_NOTIFICATION_WEBSITE_REQUIRED") + "</span>"), t.websiteInput().parents("li").addClass("error"), a.push("1")) : i.length > 0 && !t.validateWebsite(i) ? (t.websiteInput().after('<span class="help-inline">' + e.language("COM_KOMENTO_FORM_NOTIFICATION_WEBSITE_INVALID") + "</span>"), t.websiteInput().parents("li").addClass("error"), a.push("1")) : t.websiteInput().parents("li").removeClass("error"), Komento.options.config.show_tnc == 1 && t.tncCheckbox().prop("checked") == 0 && (t.errorNotification(e.language("COM_KOMENTO_FORM_NOTIFICATION_TNC_REQUIRED")), a.push("1")), (t.locationLongitude().val() == "" || t.locationLatitude().val() == "" || t.locationInput().val() == "") && t.locationInput().val(""), a.length == 0 ? t.kmt.commentupload ? t.kmt.commentupload.startUpload() : t.postComment() : t.parentid == 0 ? t.submitButton().text(e.language("COM_KOMENTO_FORM_SUBMIT")) : t.submitButton().text(e.language("COM_KOMENTO_FORM_REPLY"))
                                }, experimentalValidateComment: function() {
                                        if (Komento.options.konfig.enable_live_form_validation == 0)
                                                return t.submitButton().enable(), !0;
                                        var n = e.trim(t.nameInput().val()), r = e.trim(t.emailInput().val()), i = e.trim(t.websiteInput().val()), s = e.trim(t.commentInput().val()), o = e.trim(t.captchaResponse().val()), u = e.trim(t.recaptchaResponse().val()), a = [];
                                        t.commentInput().val().length == 0 ? a.push("1") : Komento.options.config.antispam_min_length_enable == 1 && Komento.options.config.antispam_min_length > 0 && s.length < Komento.options.config.antispam_min_comment_length && a.push("1"), (t.captchaResponse().exists() && o.length == 0 || t.recaptchaResponse().exists() && u.length == 0) && a.push("1"), n.length == 0 && (Komento.options.config.show_name == 2 && Komento.options.config.require_name == 2 || Komento.options.guest == 1 && Komento.options.config.show_name > 0 && Komento.options.config.require_name > 0) && a.push("1"), r.length == 0 && (Komento.options.config.show_email == 2 && (Komento.options.config.require_email == 2 || t.subscribeCheckbox().prop("checked")) || Komento.options.guest == 1 && Komento.options.config.show_email > 0 && (Komento.options.config.require_email > 0 || t.subscribeCheckbox().prop("checked"))) && a.push("1"), i.length == 0 && (Komento.options.config.show_website == 2 && Komento.options.config.require_website == 2 || Komento.options.guest == 1 && Komento.options.config.show_website > 0 && Komento.options.config.require_website > 0) && a.push("1"), Komento.options.config.show_tnc == 1 && t.tncCheckbox().prop("checked") == 0 && a.push("1"), a.length == 0 ? t.submitButton().enable() : t.submitButton().disable()
                                }, validateEmail: function(e) {
                                        if (Komento.options.config.enable_email_regex == 1) {
                                                var t = RegExp(Komento.options.config.email_regex);
                                                return t.test(e)
                                        }
                                        return!0
                                }, validateWebsite: function(e) {
                                        if (Komento.options.config.enable_website_regex == 1) {
                                                var t = RegExp(Komento.options.config.website_regex);
                                                return t.test(e)
                                        }
                                        return!0
                                }, postComment: function() {
                                        t.submitButton().disable();
                                        var n = [];
                                        var p = {component: Komento.component, cid: Komento.cid, comment: t.commentInput().val(), parent_id: t.parentid, depth: t.depth, username: t.usernameInput().val(), name: t.nameInput().val(), email: t.emailInput().val(), website: t.websiteInput().val(), subscribe: t.subscribeCheckbox().prop("checked"), register: t.registerCheckbox().prop("checked"), tnc: t.tncCheckbox().prop("checked"), recaptchaChallenge: t.recaptchaChallenge().val(), recaptchaResponse: t.recaptchaResponse().val(), captchaResponse: t.captchaResponse().val(), captchaId: t.captchaId().val(), latitude: t.locationLatitude().val(), longitude: t.locationLongitude().val(), address: t.locationInput().val(), contentLink: Komento.contentLink, attachments: n, pageItemId: t.pageItemId().val()};
                                        
                                // k2fields :: works only for UI = stars
                                        $$('.ratescontainer')[0].getElements('input[name^=k2frate_]:checked').each(function(v) {
                                           p[v.get('name')] = v.get('value');
                                        });
                                        t.kmt.commentupload && (n = t.kmt.commentupload.options.uploadedId), Komento.ajax("site.views.komento.addcomment", p, {success: function(n, r, i) {
                                                        var s = e(r), o = s.attr("id");
                                                        i == 1 ? (Komento.options.acl.read_comment == 1 && t.kmt.commentlist.addComment(n, r), t.successNotification(e.language("COM_KOMENTO_FORM_NOTIFICATION_SUBMITTED"))) : t.successNotification(e.language("COM_KOMENTO_FORM_NOTIFICATION_PENDING")), (t.captchaId().length != 0 || e("#recaptcha_table").length != 0) && t.reloadCaptcha(), t.parentid != 0 && t.kmt.commentlist.cancelReply(), t.commentInput().val(""), t.commentLengthCount().text("0"), t.locationForm().length > 0 && t.locationForm().controller().removeLocation(), t.parentid == 0 ? t.submitButton().text(e.language("COM_KOMENTO_FORM_SUBMIT")) : t.submitButton().text(e.language("COM_KOMENTO_FORM_REPLY")), t.kmt.commentupload && (t.kmt.commentupload.options.uploadedId = [])
                                                        // k2fields
                                                        Komento.rater.resetRating();
                                                }, fail: function(n) {
                                                        t.errorNotification(n), t.submitButton().text(e.language("COM_KOMENTO_ERROR"))
                                                }, captcha: function(n) {
                                                        Komento.options.config.antispam_captcha_type == 1 ? Recaptcha.reload() : (t.captchaImage().attr("src", n.image), t.captchaId().val(n.id), t.captchaResponse().val("")), t.parentid == 0 ? t.submitButton().text(e.language("COM_KOMENTO_FORM_SUBMIT")) : t.submitButton().text(e.language("COM_KOMENTO_FORM_REPLY"))
                                                }, subscribe: function() {
                                                        t.subscribeForm().text(e.language("COM_KOMENTO_FORM_NOTIFICATION_SUBSCRIBED")).addClass("subscribed")
                                                }, confirmSubscribe: function() {
                                                        t.subscribeForm().text(e.language("COM_KOMENTO_FORM_NOTIFICATION_SUBSCRIBE_CONFIRMATION_REQUIRED")).addClass("subscribed")
                                                }, subscribeError: function() {
                                                        t.subscribeForm().text(e.language("COM_KOMENTO_FORM_NOTIFICATION_SUBSCRIBE_ERROR"))
                                                }, notification: function(e) {
                                                        t.notification(e)
                                                }, error: function(n) {
                                                        t.errorNotification(n.statusText), e.bugReport(n), t.submitButton().text(e.language("COM_KOMENTO_ERROR"))
                                                }})
                                }, reloadCaptcha: function() {
                                        Komento.options.config.antispam_captcha_type == 1 ? Recaptcha.reload() : Komento.ajax("site.views.komento.reloadCaptcha", {component: Komento.component}, {success: function(e) {
                                                        t.captchaImage().attr("src", e.image), t.captchaId().val(e.id), t.captchaResponse().val("")
                                                }})
                                }, staticReply: function(n) {
                                        var r = n.parentid.split("-")[1];
                                        t.parentid = r, t.depth = parseInt(n.depth) + 1;
                                        var i = e("#" + parentid), s = i.find(".kmt-avatar:not(.parentContainer > .kmt-avatar)").clone(), o = i.find(".kmt-author:not(.parentContainer > .kmt-author)").clone(), u = i.find(".kmt-time:not(.parentContainer > .kmt-time)").clone(), a = i.find(".commentText:not(.parentContainer > .commentText)").clone(), f = '<a href="javascript:void(0);" class="cancelStaticReply">x</a>' + e.language("COM_KOMENTO_FORM_IN_REPLY_TO") + '<a href="' + Komento.contentLink + "#" + parentid + '" class="parentLink kmt-parent-link">' + "#" + r + "</a>", l = e('<span class="parentContainer hidden"></span>');
                                        l.html("").append(s).append(o).append(u).append(a), t.element.find("h3.kmt-title").html("").append(f).append(l), t.submitButton().text(e.language("COM_KOMENTO_FORM_REPLY")), t.element.scroll()
                                }, cancelStaticReply: function() {
                                        t.parentid = 0, t.depth = 0, t.element.find("h3.kmt-title").text(e.language("COM_KOMENTO_FORM_LEAVE_YOUR_COMMENTS"))
                                }, reply: function(n) {
                                        t.loadForm(), t.parentid = n.id, t.depth = parseInt(n.depth) + 1, t.element.find("h3.kmt-title").text(e.language("COM_KOMENTO_FORM_REPLY")), t.element.find(".submitButton").text(e.language("COM_KOMENTO_FORM_REPLY")), n.mine.append(t.element).scroll()
                                }, cancelReply: function() {
                                        t.hideForm(), t.element.find("h3.kmt-title").text(e.language("COM_KOMENTO_FORM_LEAVE_YOUR_COMMENTS")), t.parentid = 0, t.depth = 0, t.element.find(".submitButton").text(e.language("COM_KOMENTO_FORM_SUBMIT")), Komento.options.config.form_position == 0 ? Komento.options.config.tabbed_comments == 0 ? e(".commentTools").before(t.element) : e(".fameList").before(t.element) : Komento.options.config.tabbed_comments == 0 ? e(".commentList").after(t.element) : e(".fameList").after(t.element)
                                }, unsubscribe: function() {
                                        Komento.ajax("site.views.komento.unsubscribe", {component: Komento.component, cid: Komento.cid}, {success: function() {
                                                        t.subscribeForm().text(e.language("COM_KOMENTO_FORM_NOTIFICATION_UNSUBSCRIBED")).removeClass("subscribed")
                                                }, fail: function() {
                                                        t.subscribeForm().text(e.language("COM_KOMENTO_ERROR"))
                                                }})
                                }, errorNotification: function(e) {
                                        t.formAlert().removeClass("success").addClass("error"), t.notification(e)
                                }, successNotification: function(e) {
                                        t.formAlert().removeClass("error").addClass("success");
                                        var n = parseInt(Komento.options.config.autohide_form_notification);
                                        t.notification(e, n)
                                }, notification: function(e, n) {
                                        t.formAlert().show(), t.formAlert().append("<li>" + e + "</li>"), n == 1 && setTimeout(function() {
                                                t.closeNotification()
                                        }, 5e3)
                                }, closeNotification: function() {
                                        t.formAlert().hide()
                                }, clearNotification: function() {
                                        t.formAlert().html("").removeClass("error").hide()
                                }}
                }), t.resolve()
        })
});