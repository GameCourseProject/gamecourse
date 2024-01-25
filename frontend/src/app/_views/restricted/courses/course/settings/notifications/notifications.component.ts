import { Component } from "@angular/core";
import { ActivatedRoute } from "@angular/router";
import { TableDataType } from "src/app/_components/tables/table-data/table-data.component";
import { Course } from "src/app/_domain/courses/course";
import { Notification } from "src/app/_domain/notifications/notification";
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
        });
    }

    /*** --------------------------------------------- ***/
    /*** -------------------- Init ------------------- ***/
    /*** --------------------------------------------- ***/

    async getCourse(courseID: number): Promise<void> {
        this.course = await this.api.getCourseById(courseID).toPromise();
    }

    async getNotifications(courseID: number): Promise<void> {
        const notifications = await this.api.getNotificationsByCourse(courseID).toPromise();
        this.notifications = notifications.reverse();
        console.log(this.notifications);
    }

    /*** --------------------------------------------- ***/
    /*** ------------------- Table ------------------- ***/
    /*** --------------------------------------------- ***/

    headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
        {label: 'Sent To', align: 'middle'},
        {label: 'Message', align: 'middle'},
        {label: 'Seen', align: 'middle'},
    ];
    data: {type: TableDataType, content: any}[][];
    tableOptions = {
        order: [0, 'asc'],
        columnDefs: [
            { type: 'natural', targets: [0] },
        ]
    }

    buildTable(): void {
        this.loading.table = true;
        const table: {type: TableDataType, content: any}[][] = [];

        this.notifications.forEach(notif => {
          table.push([
            {type: TableDataType.TEXT, content: {text: notif.user}},
            {type: TableDataType.TEXT, content: {text: notif.message}},
            {type: TableDataType.COLOR, content: {color: notif.isShowed ? '#36D399' : '#EF6060', colorLabel: notif.isShowed ? 'Yes' : 'No'}},
          ]);
        });
        this.data = table;
        this.loading.table = false;
    }
}