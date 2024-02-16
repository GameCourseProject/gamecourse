import { Component, ViewChild } from "@angular/core";
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
export class NotificationsComponent {

    loading = {
        page: true,
        action: false,
        table: true
    }
    refreshing: boolean = true;

    course: Course;
    notifications: Notification[] = [];
    scheduledNotifications: ScheduledNotification[] = [];

    modulesToManage: ModuleNotificationManageData[] = [];
    notificationToSend: string = "";
    receiverRoles: string[] = [];
    schedule: string;

    @ViewChild('fSend', { static: false }) fSend: NgForm;
    @ViewChild('fModules', { static: false }) fModules: NgForm;

    constructor(
        private api: ApiHttpService,
        private route: ActivatedRoute
    ) { }

    ngOnInit(): void {
        this.route.parent.params.subscribe(async params => {
            const courseID = parseInt(params.id);
            await this.getCourse(courseID);
            await this.getNotifications(courseID);
            await this.getScheduledNotifications(courseID);
            this.buildTable();
            this.buildTableSchedule();
            await this.getModules(courseID);
            this.loading.page = false;
        });
    }

    /*** --------------------------------------------- ***/
    /*** -------------------- Init ------------------- ***/
    /*** --------------------------------------------- ***/

    async getCourse(courseID: number): Promise<void> {
        this.course = await this.api.getCourseById(courseID).toPromise();
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
        columnDefs: [
            { orderable: true, targets: [0] },
        ]
    }

    buildTable(): void {
        this.loading.table = true;
        const table: {type: TableDataType, content: any}[][] = [];

        this.notifications.forEach(notif => {
          table.push([
            {type: notif.dateCreated ? TableDataType.DATETIME : TableDataType.TEXT, content: notif.dateCreated ? {datetime: notif.dateCreated, datetimeFormat: "YYYY/MM/DD HH:mm"} : {text: "Never"}},
            {type: TableDataType.TEXT, content: {text: notif.user}},
            {type: TableDataType.TEXT, content: {text: notif.message}},
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
        columnDefs: [
            { orderable: true, targets: [0] },
        ]
    }

    buildTableSchedule(): void {
        this.loading.table = true;
        const table: {type: TableDataType, content: any}[][] = [];

        this.scheduledNotifications.forEach(notif => {
          table.push([
            {type: TableDataType.TEXT, content: {text: notif.roles}},
            {type: TableDataType.TEXT, content: {text: notif.message}},
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
        this.loading.action = true;

        await this.api.createNotificationForRoles(this.course.id, this.notificationToSend, this.receiverRoles).toPromise();

        // Refresh table
        await this.getNotifications(this.course.id);
        this.buildTable();

        // Reset form
        this.notificationToSend = '';
        this.receiverRoles = [];
        this.fSend.resetForm();

        this.loading.action = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Notifications sent');
    }

    async scheduleNotification() {
        this.loading.action = true;

        await this.api.scheduleNotificationForRoles(this.course.id, this.notificationToSend, this.receiverRoles, this.schedule).toPromise();

        // Refresh table
        await this.getScheduledNotifications(this.course.id);
        this.buildTableSchedule();

        // Reset form
        this.notificationToSend = '';
        this.receiverRoles = [];
        this.fSend.resetForm();

        this.loading.action = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Notification scheduled');
    }

    async saveModuleConfig() {
        this.loading.action = true;

        await this.api.toggleModuleNotifications(this.course.id, this.modulesToManage).toPromise();

        this.loading.action = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Saved Module\'s notifications settings');
    }

    async doActionOnTable(table: 'scheduled' | 'history', action: string, row: number, col: number, value?: any): Promise<void> {
        if (table === 'scheduled') {
            const notificationToActOn = this.scheduledNotifications[row];

            if (action === Action.DELETE) { 
                await this.api.cancelScheduledNotification(this.course.id, +notificationToActOn.id).toPromise();

                // Refresh table
                await this.getScheduledNotifications(this.course.id);
                this.buildTableSchedule();

                AlertService.showAlert(AlertType.SUCCESS, "Canceled scheduled notification");
            }
        }
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Helpers ------------------ ***/
    /*** --------------------------------------------- ***/

    openScheduleModal() {
        ModalService.openModal('schedule');
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
    frequency: string
}

export interface ScheduledNotification {
    id: string,
    course: string,
    roles: string,
    message: string,
    frequency: string
}