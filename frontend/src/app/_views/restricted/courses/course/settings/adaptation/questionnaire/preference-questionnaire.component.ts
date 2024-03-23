import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {NgForm} from "@angular/forms";
import {Course} from "../../../../../../../_domain/courses/course";
import {User} from "../../../../../../../_domain/users/user";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {ActivatedRoute} from "@angular/router";
import {ModalService} from "../../../../../../../_services/modal.service";
import {GameElement} from "../../../../../../../_domain/adaptation/GameElement";

@Component({
  selector: 'app-preference-questionnaire',
  templateUrl: './preference-questionnaire.component.html'
})
export class PreferenceQuestionnaireComponent implements OnInit{

  @Input() course: Course;
  @Input() user: User;
  @Input() gameElement: GameElement;

  @Input() questionnaires: QuestionnaireManageData[];

  @Output() questionnairesAfterSubmit = new EventEmitter<QuestionnaireManageData[]>();
  @Output() isQuestionnaire: boolean;

  @ViewChild('q', {static: false}) q: NgForm;       // questionnaire form (non-admin)

  loading = { action:false };
  mode: 'questionnaire';

  questionnaireToManage: QuestionnaireManageData;

  version: string = "2.0" // FIXME: Hardcoded the questionnaire formats (didn't want to lose data from last year)

  weekOptions = [
    {value: "Never", text: "Never"},
    {value: "At the 3rd week", text:"At the 3rd week"},
    {value: "At the 4th week", text: "At the 4th week"},
    {value: "Both at 3rd and 4th weeks", text: "Both at 3rd and 4th weeks"}
  ]

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe();
    this.questionnaireToManage = this.initQuestionnaireToManage();
    this.version = this.course.year === "2022-2023" ? "1.0" : "2.0";
  }

  async submitQuestionnaire(){
    if (
      (this.questionnaireToManage.q1 === "0" || this.questionnaireToManage.q1 === "Never") ||
      (this.questionnaireToManage.q1 === "1" && this.questionnaireToManage.q2 && this.questionnaireToManage.q3) || // version 1
      (this.questionnaireToManage.q1 === "At the 3rd week" && this.questionnaireToManage.q2 && this.questionnaireToManage.q3) || // version 2
      (this.questionnaireToManage.q1 === "At the 4th week" && this.questionnaireToManage.q2 && this.questionnaireToManage.q4) || // version 2
      (this.questionnaireToManage.q1 === "Both at 3rd and 4th weeks" && this.questionnaireToManage.q2 && this.questionnaireToManage.q3 && this.questionnaireToManage.q4) // version 2
    ) {
      this.questionnaireToManage.element = this.gameElement.module;
      await this.api.submitGameElementQuestionnaire(clearEmptyValues(this.questionnaireToManage)).toPromise();

      const index = this.questionnaires.findIndex(q => q.element === this.questionnaireToManage.element);

      // NOTE: q1, q2, q3 are not updated, but we don't need them
      this.questionnaires[index].isAnswered = true;
      this.questionnairesAfterSubmit.emit(this.questionnaires);

      ModalService.closeModal('questionnaire');
      AlertService.showAlert(AlertType.SUCCESS, 'Questionnaire Submitted');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  initQuestionnaireToManage(): QuestionnaireManageData{
    return {
      course: this.course.id,
      user: this.user.id,
      q1: null,
      q2: null,
      q3: null,
      q4: null,
      element: "",
      isAnswered: false
    };
  }

  resetQuestionnaireManage(){
    this.questionnaireToManage = this.initQuestionnaireToManage();
    this.mode = null;
    this.q.resetForm();
  }

}

export interface QuestionnaireManageData{
  course?: number,
  user?: number,
  q1?: string,
  q2?: string,
  q3?: number,
  q4?: number,
  element?: string,
  isAnswered?: boolean
}
