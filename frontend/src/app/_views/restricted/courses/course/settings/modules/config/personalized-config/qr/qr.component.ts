import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";

import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";
import {ModalService} from "../../../../../../../../../_services/modal.service";

import {User} from "../../../../../../../../../_domain/users/user";
import {Action} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";
import {Moment} from "moment";

@Component({
  selector: 'app-qr',
  templateUrl: './qr.component.html'
})
export class QrComponent implements OnInit {

  loading = {
    generator: false,
    printer: false,
    extraInfo: false,
    action: {
      qrAvailable: false,
      qrUsed: false,
      qrError: false
    }
  }

  courseID: number;

  quantity: number;
  qrCodes: {qr: string, url: string}[];
  @ViewChild('fGenerate', { static: false }) fGenerate: NgForm;

  students: {value: string, text: string}[];
  typesOfClass: {value: string, text: string}[];

  QRParticipations: QRParticipation[];
  unusedQRCodes: QRCode[];

  tables: {
    qrAvailable: {
      loading: boolean,
      headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
      data: {type: TableDataType, content: any}[][],
      tableOptions: any,
      viewingQRCode: boolean,
      qrCodeToDelete: string
    },
    qrUsed: {
      loading: boolean,
      headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
      data: {type: TableDataType, content: any}[][],
      tableOptions: any,
      mode: 'add' | 'edit',
      participationToManage: QRParticipationManageData,
      participationToDelete: QRParticipationManageData
    },
    qrError: {
      loading: boolean,
      headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
      data: {type: TableDataType, content: any}[][],
      tableOptions: any
    }
  } = {
    qrAvailable: {
      loading: true,
      headers: [
        {label: 'QR code', align: 'middle'},
        {label: 'Key', align: 'left'},
        {label: 'Actions'}
      ],
      data: null,
      tableOptions: {
        order: [[ 1, 'asc' ]], // default order
        columnDefs: [
          { type: 'natural', targets: [1] },
          { orderable: false, targets: [0, 2] }
        ]
      },
      viewingQRCode: false,
      qrCodeToDelete: null
    },
    qrUsed: {
      loading: true,
      headers: [
        {label: 'Name (sorting)', align: 'left'},
        {label: 'User', align: 'left'},
        {label: 'Student Nr', align: 'middle'},
        {label: 'Type', align: 'middle'},
        {label: 'Lecture Nr', align: 'middle'},
        {label: 'Date (timestamp sorting)', align: 'middle'},
        {label: 'Date', align: 'middle'},
        {label: 'Actions'}
      ],
      data: null,
      tableOptions: {
        order: [[ 5, 'desc' ]], // default order
        columnDefs: [
          { type: 'natural', targets: [0, 1, 2, 3, 4, 5, 6] },
          { orderData: 0,   targets: 1 },
          { orderData: 5,   targets: 6 },
          { orderable: false, targets: [7] }
        ]
      },
      mode: null,
      participationToManage: this.initParticipationToManage(),
      participationToDelete: null
    },
    qrError: {
      loading: true,
      headers: [
        {label: 'Name (sorting)', align: 'left'},
        {label: 'Student', align: 'left'},
        {label: 'Student Nr', align: 'middle'},
        {label: 'Error', align: 'middle'},
        {label: 'Date (timestamp sorting)', align: 'middle'},
        {label: 'Date', align: 'middle'}
      ],
      data: null,
      tableOptions: {
        order: [[ 4, 'desc' ]], // default order
        columnDefs: [
          { type: 'natural', targets: [0, 1, 2, 3, 4, 5] },
          { orderData: 0,   targets: 1 },
          { orderData: 4,   targets: 5 }
        ]
      }
    }
  }

  @ViewChild('f', { static: false }) f: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.buildQRUnusedTable();
      await this.buildQRUsedTable();
      await this.buildQRErrorTable();
    });
  }

  get Action(): typeof Action {
    return Action;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getExtraInfo() {
    this.loading.extraInfo = true;

    // Gets students
    if (!this.students) {
      let students = await this.api.getCourseUsersWithRole(this.courseID, "Student", true).toPromise();
      students = students.sort((a, b) => a.name.localeCompare(b.name));
      this.students = students.map(student => {
        return {value: 'id-' + student.id, text: student.name}
      });
    }

    // Get types of classes
    if (!this.typesOfClass)
      this.typesOfClass = (await this.api.getTypesOfClass().toPromise()).map(type => {
        return {value: type, text: type}
      });

    this.loading.extraInfo = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Tables ------------------- ***/
  /*** --------------------------------------------- ***/

  async buildQRUnusedTable() {
    this.tables.qrAvailable.loading = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.unusedQRCodes = await this.api.getUnusedQRCodes(this.courseID).toPromise();
    this.unusedQRCodes.forEach(QRCode => {
      table.push([
        {type: TableDataType.IMAGE, content: {imgSrc: QRCode.qrcode}},
        {type: TableDataType.TEXT, content: {text: QRCode.qrkey}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.VIEW, Action.DELETE]}},
      ]);
    });

    this.tables.qrAvailable.data = table;
    this.tables.qrAvailable.loading = false;
  }

  async buildQRUsedTable() {
    this.tables.qrUsed.loading = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.QRParticipations = await this.api.getClassParticipations(this.courseID).toPromise();
    this.QRParticipations.forEach(p => {
      table.push([
        {type: TableDataType.TEXT, content: {text: p.user.nickname ?? p.user.name}},
        {type: TableDataType.AVATAR, content: {avatarSrc: p.user.photoUrl, avatarTitle: p.user.nickname ?? p.user.name, avatarSubtitle: p.user.major}},
        {type: TableDataType.NUMBER, content: {value: p.user.studentNumber, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: p.classType}},
        {type: TableDataType.NUMBER, content: {value: p.classNr, valueFormat: 'none'}},
        {type: TableDataType.NUMBER, content: {value: p.date.unix()}},
        {type: TableDataType.DATETIME, content: {datetime: p.date}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.EDIT, Action.DELETE]}},
      ]);
    });

    this.tables.qrUsed.data = table;
    this.tables.qrUsed.loading = false;
  }

  async buildQRErrorTable() {
    this.tables.qrError.loading = true;

    const table: { type: TableDataType, content: any }[][] = [];
    const QRErrors = await this.api.getQRCodeErrors(this.courseID).toPromise();
    QRErrors.forEach(error => {
      table.push([
        {type: TableDataType.TEXT, content: {text: error.user.nickname ?? error.user.name}},
        {type: TableDataType.AVATAR, content: {avatarSrc: error.user.photoUrl, avatarTitle: error.user.nickname ?? error.user.name, avatarSubtitle: error.user.major}},
        {type: TableDataType.NUMBER, content: {value: error.user.studentNumber, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: error.message}},
        {type: TableDataType.NUMBER, content: {value: error.date.unix()}},
        {type: TableDataType.DATETIME, content: {datetime: error.date}}
      ]);
    });

    this.tables.qrError.data = table;
    this.tables.qrError.loading = false;
  }

  async doAction(table: 'available' | 'used', action: string) {
    if (table === 'used' && action === 'Add participation') {
      this.tables.qrUsed.mode = 'add';
      this.tables.qrUsed.participationToManage = this.initParticipationToManage();
      ModalService.openModal('participation-manage');
      await this.getExtraInfo();
    }
  }

  async doActionOnTable(table: 'available' | 'used', action: string, row: number, col: number, value?: any): Promise<void> {
    if (table === 'available' && action === Action.VIEW) {
      const QRCodeToActOn = this.unusedQRCodes[row];
      this.qrCodes = [{qr: QRCodeToActOn.qrcode, url: QRCodeToActOn.qrURL}];
      this.tables.qrAvailable.viewingQRCode = true;
      ModalService.openModal('qr-codes');

    } else if (table === 'used' && action === Action.EDIT) {
      this.tables.qrUsed.mode = 'edit';
      const participationToActOn = this.QRParticipations[row];
      const participationToManage: QRParticipationManageData = {
        id: participationToActOn.id,
        qrKey: participationToActOn.qrKey,
        userId: 'id-' + participationToActOn.user.id,
        classNr: participationToActOn.classNr,
        classType: participationToActOn.classType
      }
      this.tables.qrUsed.participationToManage = this.initParticipationToManage(participationToManage);
      ModalService.openModal('participation-manage');
      if (!this.students || !this.typesOfClass) await this.getExtraInfo();

    } else if (table === 'used' && action === Action.DELETE) {
      const participationToActOn = this.QRParticipations[row];
      this.tables.qrUsed.participationToDelete = {
        id: participationToActOn.id,
        qrKey: participationToActOn.qrKey,
        userId: 'id-' + participationToActOn.user.id,
        classNr: participationToActOn.classNr,
        classType: participationToActOn.classType
      };
      ModalService.openModal('delete-verification');

    } else if (table === 'available' && action === Action.DELETE) {
      const QRCodeToActOn = this.unusedQRCodes[row];
      this.tables.qrAvailable.qrCodeToDelete = QRCodeToActOn.qrkey;
      ModalService.openModal('delete-verification');
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  // QR CODES
  async generateQRCodes() {
    if (this.fGenerate.valid) {
      this.loading.generator = true;

      try {
        this.qrCodes = await this.api.generateQRCodes(this.courseID, this.quantity).toPromise();
        await this.buildQRUnusedTable();
        ModalService.openModal('qr-codes')
        this.loading.generator = false;

      } catch (error) {
        this.loading.generator = false;
      }

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  printQRCodes() {
    this.loading.printer = true;

    const myWindow = window.open('', 'PRINT');
    const codes = document.getElementsByClassName("code");
    const len = codes.length;

    // Divide codes into pages
    const maxPerPage = 16;
    for (let i = 0; i < len; i += maxPerPage) {
      // Create grid
      const div = document.createElement("div");
      div.classList.add("qr-codes");
      div.style.display = "grid";
      div.style.gridTemplateColumns = "25% 25% 25% 25%";
      div.style.pageBreakInside = "avoid";

      // Add codes
      for (let j = i; j < (i + maxPerPage > len ? len : i + maxPerPage); j++) {
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

    this.loading.printer = false;
  }

  async deleteQrCode(key: string): Promise<void> {
    this.loading.action.qrAvailable = true;

    await this.api.deleteQRCode(this.courseID, key).toPromise();
    await this.buildQRUnusedTable();

    this.loading.action.qrAvailable = false;
    ModalService.closeModal('delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'QR code deleted');
  }


  // CLASS PARTICIPATIONS

  async addParticipation(): Promise<void> {
    if (this.f.valid) {
      this.loading.action.qrUsed = true;

      const newParticipation = this.tables.qrUsed.participationToManage;
      const userId = parseInt(newParticipation.userId.substring(3));
      const userName = this.students.find(student => student.value = newParticipation.userId).text;
      await this.api.addQRParticipation(this.courseID, userId, newParticipation.classNr, newParticipation.classType).toPromise();
      await this.buildQRUsedTable();

      this.loading.action.qrUsed = false;
      ModalService.closeModal('participation-manage');
      this.resetParticipationManage();
      AlertService.showAlert(AlertType.SUCCESS, 'New class participation added for student \'' + userName + '\'');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editParticipation(): Promise<void> {
    if (this.f.valid) {
      this.loading.action.qrUsed = true;

      const editedParticipation = this.tables.qrUsed.participationToManage;
      const userName = this.students.find(student => student.value = editedParticipation.userId).text;
      await this.api.editQRParticipation(this.courseID, editedParticipation.qrKey, editedParticipation.classNr, editedParticipation.classType).toPromise();
      await this.buildQRUsedTable();

      this.loading.action.qrUsed = false;
      ModalService.closeModal('participation-manage');
      this.resetParticipationManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Participation for student \'' + userName + '\' edited');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deleteParticipation(key: string): Promise<void> {
    this.loading.action.qrUsed = true;

    await this.api.deleteQRParticipation(this.courseID, key).toPromise();
    await this.buildQRUsedTable();

    this.loading.action.qrUsed = false;
    ModalService.closeModal('delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'Participation deleted');
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initParticipationToManage(participation?: QRParticipationManageData): QRParticipationManageData {
    const participationData: QRParticipationManageData = {
      qrKey: participation?.qrKey ?? null,
      userId: participation?.userId ?? null,
      classNr: participation?.classNr ?? null,
      classType: participation?.classType ?? null
    };
    if (participation) participationData.id = participation.id;
    return participationData;
  }

  resetParticipationManage() {
    this.tables.qrUsed.mode = null;
    this.tables.qrUsed.participationToManage = this.initParticipationToManage();
    this.f.resetForm();
  }

}


export interface QRCode {
  qrkey: string,
  qrcode: string,
  qrURL: string
}

export interface QRParticipation {
  id: number,
  qrKey: string,
  user: User,
  classNr: number,
  classType: string,
  date: Moment
}

interface QRParticipationManageData {
  id?: number,
  qrKey: string,
  userId: string,
  classNr: number,
  classType: string
}
