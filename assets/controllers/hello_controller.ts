import { Controller } from '@hotwired/stimulus';

/*
 * Contrôleur d'exemple : tout élément avec data-controller="hello" active ce
 * contrôleur. Le nom "hello" vient du nom de fichier hello_controller.ts.
 */
export default class extends Controller {
    connect(): void {
        this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.ts';
    }
}
