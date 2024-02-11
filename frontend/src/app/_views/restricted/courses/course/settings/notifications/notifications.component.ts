import { Component, ViewChild } from "@angular/core";
import { NgForm } from "@angular/forms";
import { ActivatedRoute } from "@angular/router";
import { TableDataType } from "src/app/_components/tables/table-data/table-data.component";
import { Course } from "src/app/_domain/courses/course";
import { Module } from "src/app/_domain/modules/module";
import { Notification } from "src/app/_domain/notifications/notification";
import { AlertService, AlertType } from "src/app/_services/alert.service";
import { ApiHttpService } from "src/app/_services/api/api-http.service";

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

    modulesToManage: ModuleNotificationManageData[] = [];
    notificationToSend: string = "";

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
            this.loading.page = false;
            this.buildTable();
            await this.getModules(courseID);
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


    /*** --------------------------------------------- ***/
    /*** ------------------- Table ------------------- ***/
    /*** --------------------------------------------- ***/

    headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
        {label: 'Created On', align: 'middle'},
        {label: 'Sent To', align: 'middle'},
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


    /*** --------------------------------------------- ***/
    /*** ------------------ Actions ------------------ ***/
    /*** --------------------------------------------- ***/

    async sendNotification() {
        this.loading.action = true;
        await this.api.createAnnouncement(this.course.id, this.notificationToSend).toPromise();

        // Refresh table
        await this.getNotifications(this.course.id);
        this.buildTable();

        // Reset form
        this.notificationToSend = '';
        this.fSend.resetForm();

        this.loading.action = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Notifications sent');
    }

    async saveModuleConfig() {
        this.loading.action = true;

        await this.api.toggleModuleNotifications(this.course.id, this.modulesToManage).toPromise();

        this.loading.action = false;
        AlertService.showAlert(AlertType.SUCCESS, 'Saved Module\'s notifications settings');
    }

    setModuleFrequency(module: ModuleNotificationManageData, value: any): void {
        console.log(module.id);
        module["frequency"] = value;
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