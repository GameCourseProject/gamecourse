import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {ResourceManager} from "../../../_utils/resources/resource-manager";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ModalService} from "../../../_services/modal.service";
import { DomSanitizer } from "@angular/platform-browser";
import * as _ from "lodash"

@Component({
  selector: 'app-file-picker-modal',
  templateUrl: './file-picker-modal.component.html',
})
export class FilePickerModalComponent implements OnInit {

  @Input() id: string;                                  // Modal id
  @Input() type: string;                                // File type to pick from
  @Input() courseFolder: string;                        // Course data folder path (where to look for images)
  @Input() subfolderToOpen?: string;                    // Subfolder that should be open by default (although you can go to a less deep one)
  @Input() classList?: string;                          // Classes to append
  @Input() moduleId?: string;                           // In case the file-picker its to open in a module config

  @Input() actionInProgress?: boolean;                  // Show loader while action in progress
  @Input() innerClickEvents: boolean = true;            // Whether to close the modal when clicking outside

  @Input() positiveBtnText: string;                     // Positive btn text
  @Input() negativeBtnText: string = 'Cancel';          // Negative btn text

  @Output() positiveBtnClicked: EventEmitter<{path: string, type: 'image' | 'video' | 'audio'}> = new EventEmitter();

  // FILE EXTENSIONS
  readonly imageExtensions = ['.png', '.jpg', '.jpeg', '.gif'];
  readonly videoExtensions = ['.mp4', '.mov', '.wmv', '.avi', '.avchd', '.webm', '.mpeg-2'];
  readonly audioExtensions = ['.mp3', '.mpeg', '.wav', '.wave', '.mid', '.midi'];

  // UPLOAD FILE VARIABLES
  file: string | ArrayBuffer;
  fileToUpload: File = null;
  //fileType: 'image' | 'video' | 'audio';

  // GENERAL VARIABLES
  courseID: number;
  loading: boolean;
  path: string;

  originalRoot: ContentItem;                            // 'Home' -- most outer folder from where the files can be selected
  root: ContentItem;                                    // For further navigation (other folders inside the 'originalRoot')

  // Tabs for option to upload file or browse files in system
  tabs: { name: 'upload'| 'browse', selected: boolean }[] = [{ name: 'browse', selected: true }];
  //tabs: { name: 'upload'| 'browse', selected: boolean }[] = [{ name: 'upload', selected: true }, { name: 'browse', selected: false }];

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
  ) { }

  get ContentType(): typeof ContentType {
    return ContentType;
  }

  /*** ------------------------------------------ ***/
  /*** ------------------ Init ------------------ ***/
  /*** ------------------------------------------ ***/

  async ngOnInit() {
    this.path = this.courseFolder;
    this.courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);

    this.originalRoot = {
      name: 'root',
      type: ContentType.FOLDER,
      contents: await this.getFolderContents(),
      extension: this.path,
      selected: false
    }
    this.root = this.originalRoot;

    if (this.subfolderToOpen && this.root.contents) {
      const subfolder = this.root.contents?.find(content => content.name == this.subfolderToOpen);
      const updatedContents = await this.getFolderContents(subfolder);
      this.root = { ...this.root, contents: updatedContents };
    }

  }

  openModal() {
    ModalService.openModal('file-picker-' + this.id);
  }

  async getFolderContents(item?: ContentItem): Promise<ContentItem[]> {
    this.loading = true;

    let contents = item ? item.contents : await this.api.getCourseDataFolderContents(this.courseID, this.moduleId).toPromise();

    // Sort folders first
    contents.sort((a, b) => a.type === ContentType.FOLDER ? -1 : 1);

    // Prepare preview photos for image files
    for (let i = 0; i < contents.length; i++){
      contents[i].selected = false;
      if (contents[i].previewUrl && this.isImage(contents[i])) {
        contents[i].previewPhoto = new ResourceManager(this.sanitizer);
        contents[i].previewPhoto.set(contents[i].previewUrl);
      }
    }

    if (item) {
      this.path += '/' + item.name;   // New path to show in the bar
      this.root = item;               // New root
    }

    this.loading = false;
    return contents;
  }

  /*** -------------------------------------------- ***/
  /*** ----------------- General ------------------ ***/
  /*** -------------------------------------------- ***/

  onFileSelected(files: FileList): void {
    if (files.length <= 0) {
      this.fileToUpload = null;
    }
    else {
      this.fileToUpload = files.item(0);
    }
  }

  async upload() {
    if (this.fileToUpload) {
      // Save file in server
      await ResourceManager.getBase64(this.fileToUpload).then(data => this.file = data);
      const courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);

      const currentFolder = this.path.split('/').slice(2).join('/');

      await this.api.uploadFileToCourse(courseID, this.file, currentFolder, this.fileToUpload.name).toPromise();

      const foldersToOpen = this.path.split('/').slice(3);
      const newContents = await this.getFolderContents();
      const oldPath = this.path;

      // Refresh the contents of the directory
      this.loading = true;
      this.root.contents = newContents;
      while (foldersToOpen.length > 0) {
        const openNow = foldersToOpen.shift();
        this.root.contents = await this.getFolderContents(this.root.contents.find(content => content.name == openNow));
      }
      this.path = oldPath;
      this.loading = false;
    }
  }

  async submit() {
    let item = this.root.contents.find(content => content.selected);

    const fileType: 'image' | 'video' | 'audio' = this.isImage(item) ? 'image' : this.isAudio(item) ? 'audio' : 'video';

    this.positiveBtnClicked.emit({path: this.path + '/' + item.name, type: fileType});
    this.reset();
  }

  async delete(fileName: string) {
    const courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);
    const currentFolder = this.path.split('/').slice(2).join('/');

    await this.api.deleteFileFromCourse(courseID, currentFolder, fileName, false).toPromise();

    // Refresh the contents of the directory
    this.root.contents = this.root.contents.filter(content => content.name !== fileName);
  }


  /*** ---------------------------------------------- ***/
  /*** ----------------- Navigation ----------------- ***/
  /*** ---------------------------------------------- ***/

  calculatePath(){
    this.loading = true;

    if (this.root !== this.originalRoot) {
      // new path with last word removed
      const split = this.path.split('/');
      this.path = this.path.split('/').slice(0, split.length - 1).join('/');

      if (this.path === this.courseFolder) this.root = this.originalRoot;

      else {
        // get last word of path
        let path = this.path;
        let lastWord = path.split('/').pop() || '';

        this.root = this.goBack(this.originalRoot, lastWord);
      }
    }

    this.loading = false;
  }

  goBack(item: ContentItem, lastWord: string): ContentItem | null {

    if (item.type === ContentType.FOLDER && item.name === lastWord) {
      return item;
    }

    if (item.contents) {
      for (const content of item.contents) {
        const result = this.goBack(content, lastWord);
        if (result) {
          return result;
        }
      }

    }
    return null;
  }

  async reset(icon: boolean = false) {
    if (!icon) ModalService.closeModal('file-picker-' + this.id);

    // makes everything unselected (removes borders)
    for (let i = 0; i < this.root.contents.length; i++){
      this.root.contents[i].selected = false;
    }

    this.fileToUpload = null;
    this.file = null;

    this.path = this.courseFolder;
    this.root = this.originalRoot;

    if (this.subfolderToOpen && this.root.contents) {
      const subfolder = this.root.contents?.find(content => content.name == this.subfolderToOpen);
      const updatedContents = await this.getFolderContents(subfolder);
      this.root = { ...this.root, contents: updatedContents };
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleItems(items: any[], index: number) {
    for (let i = 0; i < items.length; i++) {
      if (items[i].type === ContentType.FOLDER) continue; // dont make folders selected
      items[i].selected = i === index;
    }
  }

  isImage(content: ContentItem): boolean {
    return this.imageExtensions.includes(content.extension);
  }

  isVideo(content: ContentItem): boolean {
    return this.videoExtensions.includes(content.extension);
  }

  isAudio(content: ContentItem): boolean {
    return this.audioExtensions.includes(content.extension);
  }

  // sees if there's a file selected
  isSelected(): boolean {
    if (this.root) {
      for (let i = 0; i < this.root.contents.length; i++){
        if (this.root.contents[i].selected) return false;
      }
    } return true;
  }

}

enum ContentType {
  FILE = 'file',
  FOLDER = 'folder'
}

export interface ContentItem {
  name: string,
  type: ContentType,
  extension?: string,
  previewUrl?: string,
  previewPhoto?: ResourceManager,
  selected: boolean,
  contents?: ContentItem[]
}
