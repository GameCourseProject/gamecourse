import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {ImageManager} from "../../../_utils/images/image-manager";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {exists} from "../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";

@Component({
  selector: 'app-image-picker-modal',
  templateUrl: './image-picker-modal.component.html',
  styleUrls: ['./image-picker-modal.component.scss']
})
export class ImagePickerModalComponent implements OnInit {

  @Input() id?: string;                                 // Modal id
  @Input() whereToLook: string;                         // Folder path of where to look for images
  @Input() whereToStore: string;                        // Folder path of where to store images
  @Input() classList?: string;                          // Classes to append

  @Input() isModalOpen: boolean;                        // Whether the modal is visible
  @Input() actionInProgress?: boolean;                  // Show loader while action in progress
  @Input() innerClickEvents: boolean = true;            // Whether to close the modal when clicking outside

  @Input() positiveBtnText: string;                     // Positive btn text
  @Input() negativeBtnText: string = 'Cancel';          // Negative btn text

  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() positiveBtnClicked: EventEmitter<string> = new EventEmitter();
  @Output() negativeBtnClicked: EventEmitter<void> = new EventEmitter();

  imageToUpload: File;
  imageToUploadName: string;

  image: string | ArrayBuffer;

  mode: 'upload' | 'browse' = 'upload';

  loading: boolean;
  contents: ContentItem[];
  path: string;

  constructor(
    private api: ApiHttpService
  ) { }

  get ContentType(): typeof ContentType {
    return ContentType;
  }

  get ApiEndpointsService(): typeof ApiEndpointsService {
    return ApiEndpointsService;
  }

  ngOnInit(): void {
    this.path = this.whereToLook;
    this.getContents();
  }

  onFileSelected(files: FileList): void {
    this.imageToUpload = files.item(0);
  }

  getContents() {
    // FIXME: should allow for other folders apart from course_data
    this.loading = true;
    const courseID = parseInt(this.whereToLook.split('/')[1].split('-')[0]);
    this.api.getCourseDataFolderContents(courseID)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        contents => this.contents = contents,
        error => ErrorService.set(error)
      );
  }

  getFolderContents(folder: any, path: string): ContentItem[] {
    path = path.removeWord(this.whereToLook);
    if (path[0] === '/') path = path.substr(1);

    if (path === '')
      return this.filterFoldersOrImages(folder.hasOwnProperty('files') ? folder.files : folder);

    const split = path.split('/');
    const f = folder.find(el => el.name === split[0]);
    return this.getFolderContents(f.files, split.length === 1 ? '' : split.slice(1).join('/'));
  }

  filterFoldersOrImages(items: ContentItem[]): ContentItem[] {
    return items.filter(item => item.filetype === ContentType.FOLDER || item.extension === '.png' || item.extension === '.jpg'
    || item.extension === '.gif');
  }

  goInside(item: ContentItem) {
    if (item.filetype !== ContentType.FOLDER) {
      this.selectItem(item);
      return;
    }
    this.path += '/' + item.name;
  }

  goOutside() {
    const split = this.path.split('/');
    this.path = split.slice(0, split.length - 1).join('/');
  }

  selectItem(item: ContentItem) {
    this.image = this.path + '/' + item.name;
  }

  async submit() {
    if (this.imageToUpload) {
      // Save image in server
      await ImageManager.getBase64(this.imageToUpload).then(data => this.image = data);
      this.api.uploadImage(this.image, this.whereToStore, this.imageToUploadName)
        .subscribe(
          path => {
            this.positiveBtnClicked.emit(path);
            this.closeBtnClicked.emit();
          },
          error => ErrorService.set(error)
        )

    } else {
      this.positiveBtnClicked.emit(this.image as string);
      this.closeBtnClicked.emit();
    }
  }

  isReadyToSubmit(): boolean {
    return (exists(this.imageToUpload) && exists(this.imageToUploadName) && !this.imageToUploadName.isEmpty()) ||
      exists(this.image);
  }

}

enum ContentType {
  FILE = 'file',
  FOLDER = 'folder'
}

export interface ContentItem {
  name: string,
  filetype: ContentType,
  files?: ContentItem[],
  extension?: string
}
