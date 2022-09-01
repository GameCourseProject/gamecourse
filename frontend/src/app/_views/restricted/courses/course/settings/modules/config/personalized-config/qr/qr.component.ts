import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {User} from "../../../../../../../../../_domain/users/user";

@Component({
  selector: 'app-qr',
  templateUrl: './qr.component.html',
  styleUrls: ['./qr.component.scss']
})
export class QrComponent implements OnInit {

  loading: boolean = true;
  courseID: number;

  quantity: number;
  qrCodes: {qr: string, url: string}[];

  tables: {
    participation: {
      loading: boolean,
      headers: string[],
      data: string[][]
    },
    qrError: {
      loading: boolean,
      headers: string[],
      data: string[][]
    }
  } = {
    participation: {
      loading: true,
      headers: null,
      data: null
    },
    qrError: {
      loading: true,
      headers: null,
      data: null
    }
  }

  isNewParticipationModalOpen: boolean;
  newParticipation: {
    studentId: number,
    lectureNr: number,
    typeOfClass: string
  } = {
    studentId: null,
    lectureNr: null,
    typeOfClass: null
  };

  students: User[];
  typesOfClass: string[];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.buildParticipationTable();
      await this.buildQRErrorTable();
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async buildParticipationTable() {
    this.tables.participation.loading = true;

    this.tables.participation.headers = [
      'name', 'student nr', 'type of class', 'class nr', 'date'
    ];

    const participations = await this.api.getClassParticipations(this.courseID).toPromise();
    this.tables.participation.data = participations.map(entry => [
      entry.user.nickname ?? entry.user.name, entry.user.studentNumber.toString(), entry.classType, entry.classNr.toString(), entry.date.format('DD/MM/YYYY HH:mm:ss')
    ]);

    this.tables.participation.loading = false;
  }

  async buildQRErrorTable() {
    this.tables.qrError.loading = true;

    this.tables.qrError.headers = [
      'date', 'name', 'student nr', 'error', 'QR code key'
    ];

    const errors = await this.api.getQRCodeErrors(this.courseID).toPromise();
    this.tables.qrError.data = errors.map(entry => [
      entry.date.format('DD/MM/YYYY HH:mm:ss'), entry.user.nickname ?? entry.user.name, entry.user.studentNumber.toString(), entry.message, entry.qrKey
    ]);

    this.tables.qrError.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async generateQRCodes() {
    this.loading = true;
    this.qrCodes = await this.api.generateQRCodes(this.courseID, this.quantity).toPromise();
    this.loading = false;
  }

  printQRCodes() {
    this.loading = true;

    const myWindow = window.open('', 'PRINT');
    const codes = document.getElementsByClassName("code");

    // Divide codes into pages
    const maxPerPage = 16;
    for (let i = 0; i < this.quantity; i += maxPerPage) {
      // Create grid
      const div = document.createElement("div");
      div.classList.add("qr-codes");
      div.style.display = "grid";
      div.style.gridTemplateColumns = "25% 25% 25% 25%";
      div.style.pageBreakInside = "avoid";

      // Add codes
      for (let j = i; j < (i + maxPerPage > this.quantity ? this.quantity : i + maxPerPage); j++) {
        const code = codes[j].cloneNode(true) as HTMLElement;
        code.style.display = "flex";
        code.style.flexDirection = "column";
        code.style.alignItems = "center";
        code.style.width = "calc(100vw / 4)";
        code.style.marginBottom = "25px";
        code.style.marginLeft = "35px";
        code.style.marginRight = "36px";
        code.style.wordBreak = "break-all";
        (code.children[1] as HTMLElement).style.fontSize = "14px";
        div.append(code);
      }

      myWindow.document.body.append(div);
    }

    myWindow.focus();
    myWindow.print();

    myWindow.onafterprint = () => myWindow.close();

    this.loading = false;
  }

  async submitNewParticipation() {
    this.loading = true;

    await this.api.submitQRParticipation(this.courseID, this.newParticipation.studentId, this.newParticipation.lectureNr, this.newParticipation.typeOfClass);
    await this.buildParticipationTable();

    this.isNewParticipationModalOpen = false;
    this.clearObject(this.newParticipation);
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  async getExtraInfo() {
    this.loading = true;

    // Gets students
    if (!this.students) {
      this.students = await this.api.getCourseUsersWithRole(this.courseID, "Student", true).toPromise();
      this.students = this.students.sort((a, b) => a.name.localeCompare(b.name));
    }

    // Get types of classes
    if (!this.typesOfClass)
      this.typesOfClass = await this.api.getTypesOfClass().toPromise();

    this.loading = false;
  }

  isReadyToSubmit(): boolean {
    return this.newParticipation.studentId != null &&
      this.newParticipation.lectureNr != null &&
      this.newParticipation.typeOfClass != null;
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

}
