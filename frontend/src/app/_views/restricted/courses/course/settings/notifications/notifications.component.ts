import { Component, OnInit, ViewChild } from "@angular/core";
import { NgForm } from "@angular/forms";
import { ActivatedRoute } from "@angular/router";
import { cronExpressionToText } from "src/app/_components/inputs/date & time/input-schedule/input-schedule.component";
import { TableDataType } from "src/app/_components/tables/table-data/table-data.component";
import { Course } from "src/app/_domain/courses/course";
import { Action } from "src/app/_domain/modules/config/Action";
import { Notification } from "src/app/_domain/notifications/notification";
import { AlertService, AlertType } from "src/app/_services/alert.service";
import { ApiHttpService } from "src/app/_services/api/api-http.service";
import { ModalService } from "src/app/_services/modal.service";

@Component({
    selector: 'app-notifications',
    templateUrl: './notifications.component.html'
})
export class NotificationsComponent implements OnInit {

    /** -- COMMON VARIABLES -- **/
    loading = {
        page: true,
        table: true,
        report: false,
        modules: false,
        schedule: false,
        send: false
    }
    refreshing: boolean = true;

    course: Course;
    isAdminOrTeacher: boolean = false;

    /** -- ADMIN VARIABLES -- **/
    suggestionsEnabled: boolean;
    progressReportEnabled: boolean;
    notifications: Notification[] = [];
    scheduledNotifications: ScheduledNotification[] = [];

    modulesToManage: ModuleNotificationManageData[] = [];
    notificationToSend: string = "";
    receiverRoles: string[] = [];
    schedule: string;
    predictions: boolean;
    reportsConfig: ProgressReportConfig;

    notificationToRead: string;                 // To display fully in pop up

    @ViewChild('fSend', { static: false }) fSend: NgForm;
    @ViewChild('fModules', { static: false }) fModules: NgForm;
    @ViewChild('fReport', { static: false }) fReport: NgForm;

    constructor(
        private api: ApiHttpService,
        private route: ActivatedRoute
    ) { }

    ngOnInit(): void {
        this.route.parent.params.subscribe(async params => {
            // Basics
            const courseID = parseInt(params.id);
            await this.getCourse(courseID);
            await this.getUser(courseID);

            if (this.isAdminOrTeacher) {
                await this.isSuggestionsEnabled(courseID);
                await this.isProgressReportEnabled(courseID);

                if (this.progressReportEnabled) {
                    await this.getProgressReportConfig(courseID);
                }

                // Notifications tables
                await this.getNotifications(courseID);
                await this.getScheduledNotifications(courseID);
                this.buildTable();
                this.buildTableSchedule();
                // Modules to config
                await this.getModules(courseID);
            }

            this.loading.page = false;
        });
    }

    /*** --------------------------------------------- ***/
    /*** -------------------- Init ------------------- ***/
    /*** --------------------------------------------- ***/

    async getCourse(courseID: number): Promise<void> {
        this.course = await this.api.getCourseById(courseID).toPromise();
    }

    async getUser(courseID: number): Promise<void> {
        const user = await this.api.getLoggedUser().toPromise();

        this.isAdminOrTeacher = user.isAdmin || await this.api.isTeacher(courseID, user.id).toPromise();
    }

    async getModules(courseID: number): Promise<void> {
        this.modulesToManage = (await this.api.getModulesWithNotifications(courseID).toPromise())
            .sort((a, b) => a.name.localeCompare(b.name));
    }

    async getNotifications(courseID: number): Promise<void> {
        const notifications = await this.api.getNotificationsByCourse(courseID).toPromise();
        this.notifications = notifications.reverse();
    }

    async getScheduledNotifications(courseID: number): Promise<void> {
        const notifications = await this.api.getScheduledNotificationsByCourse(courseID).toPromise();
        this.scheduledNotifications = notifications.reverse();
    }

    async isSuggestionsEnabled(courseID: number) {
        this.suggestionsEnabled = (await this.api.getCourseModuleById(courseID, ApiHttpService.SUGGESTIONS).toPromise()).enabled;
    }

    async isProgressReportEnabled(courseID: number) {
        this.progressReportEnabled = (await this.api.getCourseModuleById(courseID, ApiHttpService.PROGRESS_REPORT).toPromise()).enabled;
    }

    async getProgressReportConfig(courseID: number) {
        this.reportsConfig = await this.api.getProgressReportConfig(courseID).toPromise();
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Tables ------------------- ***/
    /*** --------------------------------------------- ***/

    headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
        {label: 'Sent At', align: 'middle'},
        {label: 'To', align: 'middle'},
        {label: 'Message', align: 'middle'},
        {label: 'Seen', align: 'middle'},
    ];
    data: {type: TableDataType, content: any}[][];
    tableOptions = {
        searching: true,
        lengthChange: false,
        paging: true,
        info: false,
        order: [[ 0, 'desc' ]], // default order
    }

    buildTable(): void {
        this.loading.table = true;
        const table: {type: TableDataType, content: any}[][] = [];

        this.notifications.forEach(notif => {
          table.push([
            {type: notif.dateCreated ? TableDataType.DATETIME : TableDataType.TEXT, content: notif.dateCreated ? {datetime: notif.dateCreated, datetimeFormat: "YYYY/MM/DD HH:mm"} : {text: "Never"}},
            {type: TableDataType.TEXT, content: {text: notif.user}},
            {
                type: TableDataType.BUTTON,
                content: {
                    buttonText: notif.message.length > 50 ? notif.message.substring(0, 50) + "(...)" : notif.message,
                    classList: 'btn-ghost normal-case font-normal',
                    searchBy: notif.message,
                }
            },
            {type: TableDataType.COLOR, content: {color: notif.isShowed ? '#36D399' : '#EF6060', colorLabel: notif.isShowed ? 'Yes' : 'No'}},
          ]);
        });
        this.data = table;
        this.loading.table = false;
    }

    headersSchedule: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
        {label: 'Receiving Roles', align: 'middle'},
        {label: 'Message', align: 'middle'},
        {label: 'Schedule', align: 'middle'},
        {label: 'Actions', align: 'middle'},
    ];
    dataSchedule: {type: TableDataType, content: any}[][];
    tableOptionsSchedule = {
        searching: true,
        lengthChange: false,
        paging: true,
        info: false,
        order: [[ 0, 'desc' ]], // default order
    }

    buildTableSchedule(): void {
        this.loading.table = true;
        const table: {type: TableDataType, content: any}[][] = [];

        this.scheduledNotifications.forEach(notif => {
          table.push([
            {type: TableDataType.TEXT, content: {text: notif.roles}},
            {
                type: TableDataType.BUTTON,
                content: {
                    buttonText: notif.message.length > 50 ? notif.message.substring(0, 50) + "(...)" : notif.message,
                    classList: 'btn-ghost normal-case font-normal',
                    searchBy: notif.message,
                }
            },
            {type: TableDataType.TEXT, content: {text: cronExpressionToText(notif.frequency)}},
            {type: TableDataType.ACTIONS, content: {actions: [/*Action.EDIT,*/ Action.DELETE]}},
          ]);
        });
        this.dataSchedule = table;
        this.loading.table = false;
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Actions ------------------ ***/
    /*** --------------------------------------------- ***/

    async sendNotification() {
        this.loading.send = true;

        await this.api.createNotificationForRoles(this.course.id, this.notificationToSend, this.receiverRoles).toPromise();

        // Refresh table
        await this.getNotifications(this.course.id);
        this.buildTable();

        // Reset form
        this.notificationToSend = '';
        this.receiverRoles = [];
        this.fSend.resetForm();

        this.loading.send = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Notifications sent');
    }

    async scheduleNotification() {
        this.loading.schedule = true;

        await this.api.scheduleNotificationForRoles(this.course.id, this.notificationToSend, this.receiverRoles, this.schedule).toPromise();

        // Refresh table
        await this.getScheduledNotifications(this.course.id);
        this.buildTableSchedule();

        // Reset form
        this.notificationToSend = '';
        this.receiverRoles = [];
        this.fSend.resetForm();

        this.loading.schedule = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Notification scheduled');
    }

    async saveModuleConfig() {
        this.loading.modules = true;

        await this.api.toggleModuleNotifications(this.course.id, this.modulesToManage).toPromise();

        this.loading.modules = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Saved Module\'s notifications settings');
    }

    async saveProgressReportConfig() {
        this.loading.report = true;

        await this.api.saveProgressReportConfig(this.course.id, this.reportsConfig).toPromise();

        this.loading.report = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Saved Progress Report settings');
    }

    async doActionOnTable(table: 'scheduled' | 'history', action: string, row: number, col: number, value?: any): Promise<void> {
        if (table === 'scheduled') {
            const notificationToActOn = this.scheduledNotifications[row];

            if (col === 3) {
                if (action === Action.DELETE) {
                    await this.api.cancelScheduledNotification(this.course.id, +notificationToActOn.id).toPromise();

                    // Refresh table
                    await this.getScheduledNotifications(this.course.id);
                    this.buildTableSchedule();

                    AlertService.showAlert(AlertType.SUCCESS, "Canceled scheduled notification");
                }
            }
            else if (col === 1) {
                this.notificationToRead = this.scheduledNotifications[row].message;
                ModalService.openModal('full-notification');
            }
        }
        else if (table === 'history') {
            this.notificationToRead = this.notifications[row].message;
            ModalService.openModal('full-notification');
        }
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Helpers ------------------ ***/
    /*** --------------------------------------------- ***/

    openScheduleModal() {
        ModalService.openModal('schedule');
    }

    closeFullNotificationModal() {
        ModalService.closeModal('full-notification');
    }
}


export interface NotificationManageData {
    course: number,
    user: string,
    message: string,
    isShowed: boolean
}

export interface ModuleNotificationManageData {
    id: string,
    name: string,
    isEnabled: boolean,
    frequency: string,
    format: string
}

export interface ScheduledNotification {
    id: string,
    course: string,
    roles: string,
    message: string,
    frequency: string
}

export interface ProgressReportConfig {
  frequency: string,
  isEnabled: boolean
}
