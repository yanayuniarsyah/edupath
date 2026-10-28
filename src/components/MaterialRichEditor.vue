<template>
  <div class="edupath-rich-editor bg-[#0b1324] border border-white/10 rounded-2xl flex flex-col text-white relative">
    <!-- Sticky Control Panel (Always visible when scrolling down modal or content) -->
    <div class="sticky top-0 z-30 bg-[#090f1e]/98 backdrop-blur-md rounded-t-2xl border-b border-white/10 shadow-lg transition-all">
      <!-- Header / Top Bar -->
      <div class="p-2.5 border-b border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs">
        <!-- Left: Title & Mode Toggle -->
        <div class="flex items-center gap-2">
          <span class="font-black text-xs uppercase tracking-wider text-white flex items-center gap-1.5">
            <i class="ph-bold ph-note-pencil text-[#c0ff00]"></i> {{ title || 'Editor EduPath' }}
          </span>

          <div class="flex items-center bg-black/50 p-0.5 rounded-lg border border-white/10 ml-2">
            <button
              type="button"
              @click="activeView = 'editor'"
              class="px-2.5 py-1 rounded-md text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer"
              :class="activeView === 'editor' ? 'bg-[#c0ff00] text-black shadow-sm' : 'text-white/60 hover:text-white'"
            >
              <i class="ph-bold ph-pencil-simple"></i> Editor
            </button>
            <button
              type="button"
              @click="activeView = 'preview'"
              class="px-2.5 py-1 rounded-md text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer"
              :class="activeView === 'preview' ? 'bg-[#c0ff00] text-black shadow-sm' : 'text-white/60 hover:text-white'"
            >
              <i class="ph-bold ph-eye"></i> Preview
            </button>
          </div>
        </div>

        <!-- Right: Autosave Status & Word Counter -->
        <div class="flex items-center gap-3">
          <!-- Draft restore alert -->
          <button
            v-if="hasRecoverableDraft"
            type="button"
            @click="restoreDraft"
            class="text-[10px] font-black text-amber-300 hover:text-amber-200 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded flex items-center gap-1 transition-all cursor-pointer"
          >
            <i class="ph-bold ph-clock-counter-clockwise"></i> Pulihkan Draft
          </button>

          <!-- Autosave Status Badge -->
          <div class="flex items-center gap-1.5 text-[11px]">
            <span
              class="w-2 h-2 rounded-full"
              :class="{
                'bg-emerald-400': saveStatus === 'saved',
                'bg-amber-400 animate-ping': saveStatus === 'saving',
                'bg-rose-400': saveStatus === 'error'
              }"
            ></span>
            <span class="text-[10px] font-medium text-white/60" title="Draft tersimpan otomatis di penyimpanan browser. Klik tombol 'Simpan Materi' di bawah untuk menyimpan permanen ke database server.">
              <template v-if="saveStatus === 'saved'">Draft Lokal ({{ lastSavedTime || 'Otomatis' }})</template>
              <template v-else-if="saveStatus === 'saving'">Menyimpan draft...</template>
              <template v-else-if="saveStatus === 'error'">Draft Gagal</template>
            </span>
          </div>

          <!-- Word & Char Counter -->
          <div class="text-[10px] text-white/40 font-mono hidden sm:inline">
            {{ wordCount }} kata | {{ charCount }} karakter
          </div>
        </div>
      </div>

      <!-- Word Paste Cleaned Notice -->
      <div v-if="showPasteNotice" class="bg-indigo-500/20 border-b border-indigo-500/30 px-4 py-1.5 text-[11px] text-indigo-300 flex items-center justify-between animate-fade-in">
        <span class="flex items-center gap-1.5">
          <i class="ph-bold ph-check-circle"></i> Teks dari Microsoft Word berhasil dibersihkan dan disesuaikan secara otomatis.
        </span>
        <button type="button" @click="showPasteNotice = false" class="text-white/60 hover:text-white cursor-pointer">&times;</button>
      </div>

      <!-- Toolbar (Only in Editor Mode) -->
      <div v-if="activeView === 'editor'" class="p-2 bg-black/40 flex flex-wrap items-center gap-1.5 text-xs select-none">
        <!-- Undo / Redo -->
        <button type="button" @mousedown.prevent @click="exec('undo')" title="Undo (Ctrl+Z)" class="tool-btn"><i class="ph-bold ph-arrow-u-up-left"></i></button>
        <button type="button" @mousedown.prevent @click="exec('redo')" title="Redo (Ctrl+Y)" class="tool-btn"><i class="ph-bold ph-arrow-u-up-right"></i></button>

        <span class="divider"></span>

        <!-- Quick 1-Click Heading Buttons: H1 Judul, H2 Sub Judul, H3, P (Preserves selection via @mousedown.prevent) -->
        <div class="flex items-center gap-1 bg-black/50 p-0.5 rounded-lg border border-white/10">
          <button
            type="button"
            @mousedown.prevent
            @click="applyHeading('h1')"
            title="Judul Utama (H1)"
            class="px-2 py-1 rounded text-[11px] font-black text-white/80 hover:text-white hover:bg-white/10 cursor-pointer flex items-center gap-1 transition-colors"
          >
            <span class="font-mono text-xs font-black">H1</span>
            <span class="text-[10px] hidden md:inline">Judul</span>
          </button>
          <button
            type="button"
            @mousedown.prevent
            @click="applyHeading('h2')"
            title="Beri Efek Sub Judul (H2)"
            class="px-2 py-1 rounded text-[11px] font-black text-[#c0ff00] bg-[#c0ff00]/15 hover:bg-[#c0ff00]/25 border border-[#c0ff00]/30 cursor-pointer flex items-center gap-1 transition-colors shadow-sm"
          >
            <span class="font-mono text-xs font-black">H2</span>
            <span class="text-[10px] font-bold">Sub Judul</span>
          </button>
          <button
            type="button"
            @mousedown.prevent
            @click="applyHeading('h3')"
            title="Subseksi (H3)"
            class="px-1.5 py-1 rounded text-[11px] font-bold text-white/70 hover:text-white hover:bg-white/10 cursor-pointer transition-colors"
          >
            <span class="font-mono text-xs">H3</span>
          </button>
          <button
            type="button"
            @mousedown.prevent
            @click="applyHeading('p')"
            title="Paragraf Normal (P)"
            class="px-1.5 py-1 rounded text-[11px] font-bold text-white/40 hover:text-white hover:bg-white/10 cursor-pointer transition-colors"
          >
            <span class="text-[10px]">Normal</span>
          </button>
        </div>

        <!-- Heading / Paragraph Selector Dropdown -->
        <select 
          @mousedown="saveCurrentSelection"
          @change="applyFormatBlock($event)" 
          class="tool-select" 
          title="Pilihan Gaya Teks"
        >
          <option value="p">Gaya: Normal</option>
          <option value="h1">Judul Utama (H1)</option>
          <option value="h2">Subjudul Bab (H2)</option>
          <option value="h3">Subseksi (H3)</option>
          <option value="h4">Topik Kecil (H4)</option>
        </select>

        <span class="divider"></span>

        <!-- Font Styles -->
        <button type="button" @mousedown.prevent @click="exec('bold')" title="Tebal (Bold, Ctrl+B)" class="tool-btn"><i class="ph-bold ph-text-b"></i></button>
        <button type="button" @mousedown.prevent @click="exec('italic')" title="Miring (Italic, Ctrl+I)" class="tool-btn"><i class="ph-bold ph-text-italic"></i></button>
        <button type="button" @mousedown.prevent @click="exec('underline')" title="Garis Bawah (Underline, Ctrl+U)" class="tool-btn"><i class="ph-bold ph-text-underline"></i></button>
        <button type="button" @mousedown.prevent @click="exec('strikeThrough')" title="Coret (Strikethrough)" class="tool-btn"><i class="ph-bold ph-text-strikethrough"></i></button>
        
        <!-- Pangkat Cepat (Superscript) & Indeks Bawah Cepat (Subscript) -->
        <button type="button" @mousedown.prevent @click="exec('superscript')" title="Pangkat Cepat (Superscript, x²)" class="tool-btn font-bold font-mono text-[11px] px-1.5 hover:text-[#c0ff00]">x²</button>
        <button type="button" @mousedown.prevent @click="exec('subscript')" title="Indeks Bawah Cepat (Subscript, x₂)" class="tool-btn font-bold font-mono text-[11px] px-1.5 hover:text-[#c0ff00]">x₂</button>

        <span class="divider"></span>

        <!-- Alignment -->
        <button type="button" @mousedown.prevent @click="exec('justifyLeft')" title="Rata Kiri" class="tool-btn"><i class="ph-bold ph-text-align-left"></i></button>
        <button type="button" @mousedown.prevent @click="exec('justifyCenter')" title="Rata Tengah" class="tool-btn"><i class="ph-bold ph-text-align-center"></i></button>
        <button type="button" @mousedown.prevent @click="exec('justifyRight')" title="Rata Kanan" class="tool-btn"><i class="ph-bold ph-text-align-right"></i></button>
        <button type="button" @mousedown.prevent @click="exec('justifyFull')" title="Rata Kiri-Kanan" class="tool-btn"><i class="ph-bold ph-text-align-justify"></i></button>

        <span class="divider"></span>

        <!-- Lists & Indent -->
        <button type="button" @mousedown.prevent @click="exec('insertUnorderedList')" title="Daftar Poin (Bullet)" class="tool-btn"><i class="ph-bold ph-list-bullets"></i></button>
        <button type="button" @mousedown.prevent @click="exec('insertOrderedList')" title="Daftar Angka (Numbering)" class="tool-btn"><i class="ph-bold ph-list-numbers"></i></button>
        <button type="button" @mousedown.prevent @click="exec('outdent')" title="Kurangi Indentasi" class="tool-btn"><i class="ph-bold ph-text-outdent"></i></button>
        <button type="button" @mousedown.prevent @click="exec('indent')" title="Tambah Indentasi" class="tool-btn"><i class="ph-bold ph-text-indent"></i></button>

        <span class="divider"></span>

        <!-- Advanced Inserts: Table, Image, Math, Callouts, Link -->
        <button type="button" @mousedown.prevent @click="showTableModal = true" title="Sisipkan Tabel" class="tool-btn text-indigo-300 hover:text-white">
          <i class="ph-bold ph-table"></i> Tabel
        </button>

        <button type="button" @mousedown.prevent @click="showImageModal = true" title="Sisipkan Gambar (Upload Media)" class="tool-btn text-emerald-300 hover:text-white">
          <i class="ph-bold ph-image"></i> Gambar
        </button>

        <button type="button" @mousedown.prevent @click="showMathModal = true" title="Sisipkan Rumus Matematika (KaTeX)" class="tool-btn text-[#c0ff00] hover:text-white">
          <span class="font-serif font-bold text-xs italic">∑</span> Rumus
        </button>

        <!-- Special Callout Blocks -->
        <div class="relative inline-block">
          <button
            type="button"
            @mousedown.prevent
            @click="showCalloutDropdown = !showCalloutDropdown"
            class="tool-btn text-amber-300 hover:text-white flex items-center gap-1"
            title="Sisipkan Blok Penjelas (Definisi, Catatan, Tips, Contoh, Peringatan)"
          >
            <i class="ph-bold ph-cube"></i> Blok Khusus <i class="ph-bold ph-caret-down text-[9px]"></i>
          </button>

          <div
            v-if="showCalloutDropdown"
            class="absolute left-0 top-full mt-1 w-48 bg-[#0e1726] border border-white/15 rounded-xl shadow-2xl py-1 z-30 animate-fade-in"
          >
            <button type="button" @mousedown.prevent @click="insertCallout('definition')" class="callout-opt text-cyan-300 hover:bg-cyan-500/10">
              <i class="ph-bold ph-book-open"></i> Definisi Konsep
            </button>
            <button type="button" @mousedown.prevent @click="insertCallout('note')" class="callout-opt text-indigo-300 hover:bg-indigo-500/10">
              <i class="ph-bold ph-info"></i> Catatan Materi
            </button>
            <button type="button" @mousedown.prevent @click="insertCallout('tip')" class="callout-opt text-emerald-300 hover:bg-emerald-500/10">
              <i class="ph-bold ph-lightbulb"></i> Tips & Trik Cepat
            </button>
            <button type="button" @mousedown.prevent @click="insertCallout('example')" class="callout-opt text-amber-300 hover:bg-amber-500/10">
              <i class="ph-bold ph-lightning"></i> Contoh Soal
            </button>
            <button type="button" @mousedown.prevent @click="insertCallout('warning')" class="callout-opt text-rose-300 hover:bg-rose-500/10">
              <i class="ph-bold ph-warning"></i> Peringatan / Jebakan
            </button>
          </div>
        </div>

        <span class="divider"></span>

        <!-- Link & Divider -->
        <button type="button" @mousedown.prevent @click="openLinkModal" title="Sisipkan Tautan" class="tool-btn"><i class="ph-bold ph-link"></i></button>
        <button type="button" @mousedown.prevent @click="insertSectionBreak" title="Batas Bagian / Bab Baru" class="tool-btn"><i class="ph-bold ph-columns"></i> Seksi Baru</button>
        <button type="button" @mousedown.prevent @click="exec('removeFormat')" title="Hapus Format" class="tool-btn text-white/40 hover:text-white"><i class="ph-bold ph-text-strikethrough"></i> Reset</button>
      </div>
    </div>

    <!-- Main Content Area: Editor vs Preview -->
    <div class="flex-grow flex flex-col max-h-[650px] relative bg-[#060b14]" :style="{ minHeight: minHeight || '320px' }">
      <!-- 1. WYSIWYG EDITOR MODE -->
      <div
        v-show="activeView === 'editor'"
        ref="editorEl"
        contenteditable="true"
        @input="onContentInput"
        @paste="handlePaste"
        @click="handleEditorClick"
        @mouseup="saveCurrentSelection"
        @keyup="saveCurrentSelection"
        @select="saveCurrentSelection"
        @focus="saveCurrentSelection"
        class="edupath-prose flex-grow p-5 overflow-y-auto focus:outline-none text-slate-100 text-sm leading-relaxed"
        :style="{ minHeight: minHeight || '320px' }"
        :data-placeholder="placeholder || 'Mulai tulis materi pembelajaran atau butir soal di sini... (Mendukung rumus KaTeX, tabel, gambar, atau teks dari Word)'"
      ></div>

      <!-- 2. PREVIEW MODE -->
      <div v-show="activeView === 'preview'" class="flex-grow flex flex-col overflow-hidden bg-black/40">
        <!-- Preview Device Selector -->
        <div class="px-4 py-2 border-b border-white/10 flex items-center justify-between bg-black/30 text-xs">
          <div class="flex items-center gap-2 text-white/60">
            <span>Simulasi Tampilan Siswa:</span>
            <button
              type="button"
              @click="previewDevice = 'desktop'"
              class="px-2.5 py-1 rounded text-[11px] font-bold flex items-center gap-1 transition-all"
              :class="previewDevice === 'desktop' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40' : 'text-white/40 hover:text-white'"
            >
              <i class="ph-bold ph-desktop"></i> Desktop
            </button>
            <button
              type="button"
              @click="previewDevice = 'mobile'"
              class="px-2.5 py-1 rounded text-[11px] font-bold flex items-center gap-1 transition-all"
              :class="previewDevice === 'mobile' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40' : 'text-white/40 hover:text-white'"
            >
              <i class="ph-bold ph-device-mobile"></i> Mobile (375px)
            </button>
          </div>
          <span class="text-[10px] text-white/40">Tampilan hasil render materi real-time</span>
        </div>

        <!-- Render Container -->
        <div class="flex-grow overflow-y-auto p-6 flex justify-center">
          <div
            :class="previewDevice === 'mobile' ? 'w-[375px] max-w-full bg-[#0d1427] border border-white/15 rounded-3xl p-5 shadow-2xl my-auto' : 'w-full max-w-4xl bg-[#0d1427]/50 p-6 rounded-2xl border border-white/10'"
          >
            <div class="mb-4 pb-3 border-b border-white/10 flex items-center justify-between">
              <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-[#c0ff00]/15 text-[#c0ff00]">
                {{ previewComponent || 'Materi Belajar' }}
              </span>
              <span class="text-[11px] text-white/50 font-bold">{{ previewTopic || 'EduPath Learning Path' }}</span>
            </div>

            <!-- Preview HTML Output with rendered KaTeX formulas -->
            <div ref="previewContentEl" class="edupath-prose text-slate-100 text-sm leading-relaxed" v-html="sanitizedHtml"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Insert Table -->
    <div v-if="showTableModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-[#0e1726] border border-white/15 rounded-2xl w-full max-w-sm p-5 space-y-4 shadow-2xl text-white animate-fade-in">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <h4 class="font-black text-sm flex items-center gap-2 text-indigo-300">
            <i class="ph-bold ph-table"></i> Buat Tabel Baru
          </h4>
          <button type="button" @click="showTableModal = false" class="text-white/40 hover:text-white">&times;</button>
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Jumlah Baris</label>
            <input v-model.number="tableRows" type="number" min="1" max="50" class="input-field" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Jumlah Kolom</label>
            <input v-model.number="tableCols" type="number" min="1" max="10" class="input-field" />
          </div>
        </div>

        <label class="flex items-center gap-2 text-xs text-white/80 cursor-pointer pt-1">
          <input type="checkbox" v-model="tableWithHeader" class="rounded text-[#c0ff00] bg-black/40 border-white/20" />
          Sertakan Baris Header (Judul Kolom)
        </label>

        <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
          <button type="button" @click="showTableModal = false" class="btn-cancel">Batal</button>
          <button type="button" @click="insertTable" class="btn-primary">Sisipkan Tabel</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Insert / Upload Image -->
    <div v-if="showImageModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-[#0e1726] border border-white/15 rounded-2xl w-full max-w-md p-5 space-y-4 shadow-2xl text-white animate-fade-in">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <h4 class="font-black text-sm flex items-center gap-2 text-emerald-300">
            <i class="ph-bold ph-image"></i> Sisipkan Gambar Materi
          </h4>
          <button type="button" @click="showImageModal = false" class="text-white/40 hover:text-white">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
          <!-- Upload image file via backend endpoint (No base64) -->
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pilih File Gambar (PNG, JPG, WEBP, SVG)</label>
            <input
              type="file"
              accept="image/*"
              @change="handleImageFileSelect"
              class="w-full text-xs text-white/70 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-500 file:text-black hover:file:bg-emerald-400 cursor-pointer"
            />
          </div>

          <div v-if="isUploadingImage" class="p-2 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-300 text-xs flex items-center gap-2">
            <i class="ph-bold ph-spinner animate-spin"></i> Mengunggah gambar ke server EduPath...
          </div>

          <div v-if="imageForm.url" class="p-2 bg-black/40 rounded-lg border border-white/10">
            <img :src="imageForm.url" alt="Preview" class="max-h-36 mx-auto rounded object-contain" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Keterangan Gambar / Caption (Opsional)</label>
            <input v-model="imageForm.caption" type="text" placeholder="Misal: Gambar 1. Diagram Siklus Krebs" class="input-field" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Alt Text (Aksesibilitas)</label>
            <input v-model="imageForm.alt" type="text" placeholder="Deskripsi singkat gambar" class="input-field" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Ukuran Gambar</label>
              <select v-model="imageForm.width" class="input-field">
                <option value="100%">Lebar Penuh (100%)</option>
                <option value="75%">Sedang (75%)</option>
                <option value="50%">Separuh (50%)</option>
                <option value="30%">Kecil (30%)</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Perataan (Align)</label>
              <select v-model="imageForm.align" class="input-field">
                <option value="center">Tengah (Center)</option>
                <option value="left">Kiri (Left)</option>
                <option value="right">Kanan (Right)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
          <button type="button" @click="showImageModal = false" class="btn-cancel">Batal</button>
          <button type="button" :disabled="!imageForm.url || isUploadingImage" @click="insertImage" class="btn-primary">
            Sisipkan Gambar
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Math Formula (KaTeX) -->
    <div v-if="showMathModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-[#0e1726] border border-white/15 rounded-2xl w-full max-w-2xl p-5 space-y-4 shadow-2xl text-white animate-fade-in max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center font-serif font-black text-sm italic">∑</span>
            <div>
              <h4 class="font-black text-sm text-white">Editor Persamaan Matematika & Sains (KaTeX)</h4>
              <p class="text-[10px] text-white/50">Mendukung penulisan pangkat, akar, pecahan, matriks, kalkulus, dan simbol kompleks</p>
            </div>
          </div>
          <button type="button" @click="showMathModal = false" class="text-white/40 hover:text-white cursor-pointer text-xl leading-none">&times;</button>
        </div>

        <!-- Mode Formula: Inline vs Display Block -->
        <div class="p-2.5 bg-black/40 border border-white/10 rounded-xl flex flex-wrap items-center justify-between gap-2 text-xs">
          <span class="text-[11px] font-bold text-white/70">Format Penempatan:</span>
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold" :class="mathDisplayMode === 'inline' ? 'text-[#c0ff00]' : 'text-white/50'">
              <input type="radio" v-model="mathDisplayMode" value="inline" @change="renderMathPreview" class="accent-[#c0ff00]" />
              <span>Inline (Di tengah kalimat)</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold" :class="mathDisplayMode === 'block' ? 'text-[#c0ff00]' : 'text-white/50'">
              <input type="radio" v-model="mathDisplayMode" value="block" @change="renderMathPreview" class="accent-[#c0ff00]" />
              <span>Block (Baris sendiri di tengah)</span>
            </label>
          </div>
        </div>

        <!-- Category Tabs -->
        <div class="space-y-2">
          <div class="flex flex-wrap gap-1 border-b border-white/10 pb-1.5 text-xs">
            <button
              type="button"
              v-for="cat in mathCategories"
              :key="cat.id"
              @click="mathActiveCategory = cat.id"
              class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer"
              :class="mathActiveCategory === cat.id ? 'bg-[#c0ff00] text-black shadow-sm' : 'text-white/60 hover:text-white bg-white/5'"
            >
              {{ cat.label }}
            </button>
          </div>

          <!-- Category Buttons -->
          <div class="p-2.5 bg-black/30 border border-white/10 rounded-xl max-h-36 overflow-y-auto">
            <div class="flex flex-wrap gap-1.5 text-xs">
              <button
                type="button"
                v-for="tpl in currentCategoryTemplates"
                :key="tpl.name + tpl.latex"
                @click="applyMathTemplate(tpl.latex)"
                class="math-btn px-2 py-1 rounded text-xs flex items-center gap-1 cursor-pointer"
                :title="tpl.desc || tpl.latex"
              >
                <span class="font-medium text-white/90">{{ tpl.name }}</span>
                <span class="text-[10px] text-[#c0ff00] font-mono">({{ tpl.label }})</span>
              </button>
            </div>
          </div>
        </div>

        <!-- LaTeX Input Area -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[10px] font-bold text-white/50 uppercase tracking-wider">Formula LaTeX</label>
            <button type="button" @click="mathFormulaInput = ''; renderMathPreview();" class="text-[10px] text-white/40 hover:text-rose-400 cursor-pointer">Bersihkan</button>
          </div>
          <textarea
            v-model="mathFormulaInput"
            rows="3"
            placeholder="Ketik kode LaTeX di sini, misal: f(x) = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}"
            class="input-field font-mono text-xs w-full p-2.5 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]"
          ></textarea>
        </div>

        <!-- Real-time KaTeX Live Preview -->
        <div>
          <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pratinjau Rumus (Real-Time Render)</label>
          <div class="p-4 bg-black/60 border border-amber-500/30 rounded-xl min-h-[60px] flex items-center justify-center overflow-x-auto">
            <div ref="mathPreviewEl" class="text-amber-200"></div>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex justify-between items-center pt-2 border-t border-white/10">
          <span class="text-[10px] text-white/40">KaTeX v0.19 terintegrasi penuh</span>
          <div class="flex gap-2">
            <button type="button" @click="showMathModal = false" class="btn-cancel">Batal</button>
            <button type="button" :disabled="!mathFormulaInput.trim()" @click="insertMathFormula" class="btn-primary flex items-center gap-1">
              <i class="ph-bold ph-check"></i> Sisipkan Rumus
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Insert Link -->
    <div v-if="showLinkModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-[#0e1726] border border-white/15 rounded-2xl w-full max-w-sm p-5 space-y-4 shadow-2xl text-white animate-fade-in">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <h4 class="font-black text-sm flex items-center gap-2 text-sky-300">
            <i class="ph-bold ph-link"></i> Sisipkan Hyperlink
          </h4>
          <button type="button" @click="showLinkModal = false" class="text-white/40 hover:text-white">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">URL Tujuan (https://...)</label>
            <input v-model="linkUrl" type="url" placeholder="https://example.com" class="input-field" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Teks Tautan</label>
            <input v-model="linkText" type="text" placeholder="Label teks" class="input-field" />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
          <button type="button" @click="showLinkModal = false" class="btn-cancel">Batal</button>
          <button type="button" :disabled="!linkUrl" @click="insertLink" class="btn-primary">
            Sisipkan Tautan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue';
import katex from 'katex';
import 'katex/dist/katex.min.css';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  materialId: {
    type: [String, Number],
    default: ''
  },
  leafId: {
    type: String,
    default: ''
  },
  learningObjective: {
    type: String,
    default: ''
  },
  previewComponent: {
    type: String,
    default: 'TPS'
  },
  previewTopic: {
    type: String,
    default: 'Materi Pembelajaran'
  },
  title: {
    type: String,
    default: 'Editor EduPath'
  },
  placeholder: {
    type: String,
    default: ''
  },
  minHeight: {
    type: String,
    default: '320px'
  },
  storageKeyPrefix: {
    type: String,
    default: 'edupath_editor_draft_'
  },
  compact: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'change', 'autosaved']);

// View State
const activeView = ref('editor'); // 'editor' | 'preview'
const previewDevice = ref('desktop'); // 'desktop' | 'mobile'
const editorEl = ref(null);
const previewContentEl = ref(null);
const mathPreviewEl = ref(null);

// Autosave & Draft State
const saveStatus = ref('saved'); // 'saved' | 'saving' | 'error'
const lastSavedTime = ref('');
const showPasteNotice = ref(false);
const hasRecoverableDraft = ref(false);

const draftStorageKey = computed(() => {
  return `${props.storageKeyPrefix}${props.materialId || 'new'}`;
});

// Modals State
const showTableModal = ref(false);
const tableRows = ref(3);
const tableCols = ref(3);
const tableWithHeader = ref(true);

const showImageModal = ref(false);
const isUploadingImage = ref(false);
const imageForm = reactive({
  url: '',
  caption: '',
  alt: '',
  width: '100%',
  align: 'center'
});

const showMathModal = ref(false);
const mathFormulaInput = ref('\\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}');

const showLinkModal = ref(false);
const linkUrl = ref('');
const linkText = ref('');
const savedSelection = ref(null);

const saveCurrentSelection = () => {
  const sel = window.getSelection();
  if (sel && sel.getRangeAt && sel.rangeCount > 0) {
    savedSelection.value = sel.getRangeAt(0).cloneRange();
  }
};

const showCalloutDropdown = ref(false);

// Formatting Execution
const exec = (command, value = null) => {
  if (editorEl.value) {
    editorEl.value.focus();
  }
  document.execCommand(command, false, value);
  onContentInput();
};

const applyHeading = (tag) => {
  if (editorEl.value) {
    editorEl.value.focus();
  }
  
  // 1. Restore saved selection if current selection is lost or collapsed
  const sel = window.getSelection();
  if ((!sel || sel.rangeCount === 0 || sel.isCollapsed) && savedSelection.value) {
    try {
      sel.removeAllRanges();
      sel.addRange(savedSelection.value);
    } catch (e) {}
  }

  const activeSel = window.getSelection();
  const selectedText = activeSel ? activeSel.toString() : '';

  let success = false;
  try {
    success = document.execCommand('formatBlock', false, `<${tag}>`);
  } catch (e) {}
  if (!success) {
    try {
      success = document.execCommand('formatBlock', false, tag);
    } catch (e) {}
  }

  // 2. Fallback: Wrap directly with DOM element if formatBlock didn't work and text was selected
  if (!success && activeSel && activeSel.rangeCount > 0 && selectedText) {
    try {
      const range = activeSel.getRangeAt(0);
      const headingNode = document.createElement(tag);
      headingNode.textContent = selectedText;
      range.deleteContents();
      range.insertNode(headingNode);

      const newRange = document.createRange();
      newRange.setStartAfter(headingNode);
      newRange.collapse(true);
      activeSel.removeAllRanges();
      activeSel.addRange(newRange);
      success = true;
    } catch (err) {
      console.warn("DOM heading wrap error:", err);
    }
  }

  onContentInput();
  saveCurrentSelection();
};

const applyFormatBlock = (event) => {
  const tag = event?.target?.value || event;
  if (tag) applyHeading(tag);
};

// Word Cleaner / Sanitizer
const sanitizeWordHtml = (html) => {
  let clean = html;

  // 1. Remove comments, XML namespaces, and Mso meta tags
  clean = clean.replace(/<!--[\s\S]*?-->/gi, '');
  clean = clean.replace(/<o:p[\s\S]*?<\/o:p>/gi, '');
  clean = clean.replace(/<\/?[a-z]+:[^>]*>/gi, '');

  // 2. Remove style attributes containing mso- or office specific rules
  clean = clean.replace(/style="[^"]*mso-[^"]*"/gi, '');
  clean = clean.replace(/class="?Mso[a-zA-Z0-9]*"?/gi, '');

  // 3. Remove dirty empty spans or paragraph wrappers
  clean = clean.replace(/<span[^>]*>\s*<\/span>/gi, '');
  clean = clean.replace(/<font[^>]*>([\s\S]*?)<\/font>/gi, '$1');

  return clean;
};

const handlePaste = (e) => {
  const clipboardData = e.clipboardData || window.clipboardData;
  if (!clipboardData) return;

  const html = clipboardData.getData('text/html');
  if (html && (html.includes('urn:schemas-microsoft-com:office') || html.includes('MsoNormal') || html.includes('mso-'))) {
    e.preventDefault();
    const cleaned = sanitizeWordHtml(html);
    document.execCommand('insertHTML', false, cleaned);
    showPasteNotice.value = true;
    setTimeout(() => { showPasteNotice.value = false; }, 6000);
    onContentInput();
  }
};

// Insert Table
const insertTable = () => {
  let tableHtml = `<div class="edupath-table-wrapper my-4 overflow-x-auto"><table class="edupath-table w-full border-collapse border border-white/20 text-xs">`;

  if (tableWithHeader.value) {
    tableHtml += '<thead><tr class="bg-indigo-950/60 text-indigo-200 font-bold border-b border-white/20">';
    for (let c = 0; c < tableCols.value; c++) {
      tableHtml += `<th class="p-2.5 border border-white/20 text-left">Kolom ${c + 1}</th>`;
    }
    tableHtml += '</tr></thead>';
  }

  tableHtml += '<tbody>';
  for (let r = 0; r < tableRows.value; r++) {
    tableHtml += '<tr class="border-b border-white/10 hover:bg-white/5">';
    for (let c = 0; c < tableCols.value; c++) {
      tableHtml += `<td class="p-2 border border-white/10">Data ${r + 1}-${c + 1}</td>`;
    }
    tableHtml += '</tr>';
  }
  tableHtml += '</tbody></table></div><p><br></p>';

  if (editorEl.value) editorEl.value.focus();
  document.execCommand('insertHTML', false, tableHtml);
  showTableModal.value = false;
  onContentInput();
};

// Handle Image File Upload (Non-base64 backend endpoint)
const handleImageFileSelect = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  isUploadingImage.value = true;
  try {
    const formData = new FormData();
    formData.append('file', file);
    const token = sessionStorage.getItem('admin_token') || localStorage.getItem('auth_token');
    const apiUrl = import.meta.env.VITE_API_URL || '/api';

    const res = await fetch(`${apiUrl}/admin.php?action=upload_material_image`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formData
    });

    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Gagal mengunggah gambar');

    imageForm.url = data.url;
    if (!imageForm.alt) imageForm.alt = file.name.replace(/\.[^/.]+$/, '');
  } catch (err) {
    alert(err.message || 'Gagal mengunggah gambar ke server');
  } finally {
    isUploadingImage.value = false;
  }
};

const insertImage = () => {
  if (!imageForm.url) return;

  const alignClass = imageForm.align === 'center' ? 'mx-auto' : imageForm.align === 'right' ? 'ml-auto' : 'mr-auto';
  let figureHtml = `
    <figure class="edupath-figure my-4 text-center ${alignClass}" style="max-width: ${imageForm.width};">
      <img src="${imageForm.url}" alt="${imageForm.alt || 'Gambar Materi'}" class="rounded-xl border border-white/15 w-full shadow-lg object-contain" />
      ${imageForm.caption ? `<figcaption class="mt-2 text-xs text-white/50 italic">${imageForm.caption}</figcaption>` : ''}
    </figure>
    <p><br></p>
  `;

  if (editorEl.value) editorEl.value.focus();
  document.execCommand('insertHTML', false, figureHtml);
  showImageModal.value = false;
  imageForm.url = '';
  imageForm.caption = '';
  imageForm.alt = '';
  onContentInput();
};

// KaTeX Formulas & Math Notation
const mathDisplayMode = ref('inline'); // 'inline' | 'block'
const mathActiveCategory = ref('powers_roots');

const mathCategories = [
  { id: 'powers_roots', label: 'Pangkat & Akar' },
  { id: 'fractions_algebra', label: 'Pecahan & Aljabar' },
  { id: 'matrices_vectors', label: 'Matriks & Vektor' },
  { id: 'calculus', label: 'Kalkulus & Fungsi' },
  { id: 'trig_geom', label: 'Trigonometri & Sudut' },
  { id: 'symbols', label: 'Simbol & Logika' },
  { id: 'greek', label: 'Huruf Yunani' }
];

const mathTemplates = {
  powers_roots: [
    { name: 'Kuadrat', label: 'x²', latex: 'x^{2}', desc: 'Pangkat dua' },
    { name: 'Pangkat n', label: 'xⁿ', latex: 'x^{n}', desc: 'Pangkat n umum' },
    { name: 'Pangkat Majemuk', label: 'a^(3x+1)', latex: 'a^{3x + 1}', desc: 'Eksponen majemuk' },
    { name: 'Akar Kuadrat', label: '√x', latex: '\\sqrt{x}', desc: 'Akar pangkat 2' },
    { name: 'Akar Kubik', label: '∛x', latex: '\\sqrt[3]{x}', desc: 'Akar pangkat 3' },
    { name: 'Akar Pangkat n', label: 'ⁿ√x', latex: '\\sqrt[n]{x}', desc: 'Akar pangkat n' },
    { name: 'Rumus ABC', label: 'x1,2', latex: 'x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}', desc: 'Akar persamaan kuadrat' },
    { name: 'Persamaan Kuadrat', label: 'ax²+bx+c=0', latex: 'ax^2 + bx + c = 0', desc: 'Bentuk umum persamaan kuadrat' },
    { name: 'Eksponensial e', label: 'e^(ix)', latex: 'e^{i\\pi} + 1 = 0', desc: 'Identitas Euler' }
  ],
  fractions_algebra: [
    { name: 'Pecahan Biasa', label: 'a/b', latex: '\\frac{a}{b}', desc: 'Pecahan biasa' },
    { name: 'Pecahan Display', label: 'dfrac', latex: '\\dfrac{x + 1}{x - 1}', desc: 'Pecahan ukuran proporsional' },
    { name: 'Pecahan Campuran', label: '1 ½', latex: '1\\frac{1}{2}', desc: 'Pecahan campuran' },
    { name: 'Subskrip Indeks', label: 'x_i', latex: 'x_{i}', desc: 'Indeks variabel' },
    { name: 'Indeks Ganda', label: 'x₁, x₂', latex: 'x_{1}, x_{2}', desc: 'Dua indeks variabel' },
    { name: 'Kombinasi C(n,k)', label: 'nCk', latex: '\\binom{n}{k}', desc: 'Koefisien binomial' },
    { name: 'Faktorial', label: 'n!', latex: 'n! = n \\times (n-1)!', desc: 'Faktorial' },
    { name: 'Nilai Mutlak', label: '|x|', latex: '|x - 5| \\le 3', desc: 'Nilai mutlak' }
  ],
  matrices_vectors: [
    { name: 'Matriks 2x2', label: '2×2', latex: '\\begin{pmatrix} a & b \\\\ c & d \\end{pmatrix}', desc: 'Matriks ordo 2x2 tanda kurung' },
    { name: 'Matriks 3x3', label: '3×3', latex: '\\begin{pmatrix} a & b & c \\\\ d & e & f \\\\ g & h & i \\end{pmatrix}', desc: 'Matriks ordo 3x3' },
    { name: 'Determinan 2x2', label: '|det|', latex: '\\begin{vmatrix} a & b \\\\ c & d \\end{vmatrix}', desc: 'Determinan matriks 2x2' },
    { name: 'Matriks Kolom', label: 'Kolom', latex: '\\begin{pmatrix} x \\\\ y \\end{pmatrix}', desc: 'Vektor kolom 2D' },
    { name: 'Vektor Panah', label: 'v⃗', latex: '\\vec{v} = 2\\hat{i} + 3\\hat{j}', desc: 'Notasi vektor' },
    { name: 'Dot Product', label: 'u · v', latex: '\\vec{u} \\cdot \\vec{v} = |u||v|\\cos\\theta', desc: 'Perkalian titik vektor' }
  ],
  calculus: [
    { name: 'Limit x mendekati 0', label: 'lim', latex: '\\lim_{x \\to 0} \\frac{\\sin x}{x} = 1', desc: 'Limit fungsi' },
    { name: 'Limit x mendekati ∞', label: 'lim ∞', latex: '\\lim_{x \\to \\infty} \\left(1 + \\frac{1}{x}\\right)^x = e', desc: 'Limit tak hingga' },
    { name: 'Turunan Pertama', label: 'df/dx', latex: '\\frac{df}{dx} = \\lim_{h \\to 0} \\frac{f(x+h) - f(x)}{h}', desc: 'Definisi turunan' },
    { name: 'Turunan Aksen', label: "f'(x)", latex: "f'(x) = 3x^2 - 4x + 1", desc: 'Notasi aksen' },
    { name: 'Integral Tak Tentu', label: '∫ f(x) dx', latex: '\\int (3x^2 + 2x) \\, dx = x^3 + x^2 + C', desc: 'Integral tak tentu' },
    { name: 'Integral Tentu', label: '∫_a^b', latex: '\\int_{0}^{\\pi} \\sin(x) \\, dx', desc: 'Integral tentu dengan batas' },
    { name: 'Sigma / Deret', label: '∑', latex: '\\sum_{k=1}^{n} k^2 = \\frac{n(n+1)(2n+1)}{6}', desc: 'Penjumlahan deret' },
    { name: 'Logaritma Basis a', label: 'log_a', latex: '\\log_{a}(b) = c \\iff a^c = b', desc: 'Logaritma' },
    { name: 'Logaritma Natural', label: 'ln(x)', latex: '\\ln(x) = \\int_{1}^{x} \\frac{1}{t} \\, dt', desc: 'Logaritma natural' }
  ],
  trig_geom: [
    { name: 'Identitas Pythagoras', label: 'sin²+cos²', latex: '\\sin^2\\theta + \\cos^2\\theta = 1', desc: 'Identitas trigonometri' },
    { name: 'Tangen', label: 'tan', latex: '\\tan\\theta = \\frac{\\sin\\theta}{\\cos\\theta}', desc: 'Fungsi tangen' },
    { name: 'Aturan Sinus', label: 'a/sin A', latex: '\\frac{a}{\\sin A} = \\frac{b}{\\sin B} = \\frac{c}{\\sin C}', desc: 'Aturan sinus segitiga' },
    { name: 'Aturan Cosinus', label: 'c²=a²+b²..', latex: 'c^2 = a^2 + b^2 - 2ab\\cos C', desc: 'Aturan cosinus segitiga' },
    { name: 'Sudut & Derajat', label: '∠ABC', latex: '\\angle ABC = 60^\\circ', desc: 'Besar sudut' },
    { name: 'Phi & Lingkaran', label: 'Luas L', latex: 'L = \\pi r^2, \\quad K = 2\\pi r', desc: 'Rumus lingkaran' }
  ],
  symbols: [
    { name: 'Plus-Minus', label: '±', latex: '\\pm', desc: 'Plus minus' },
    { name: 'Kali', label: '×', latex: '\\times', desc: 'Simbol perkalian' },
    { name: 'Bagi', label: '÷', latex: '\\div', desc: 'Simbol pembagian' },
    { name: 'Kurang Dari Sama Dengan', label: '≤', latex: '\\le', desc: 'Pertidaksamaan' },
    { name: 'Lebih Dari Sama Dengan', label: '≥', latex: '\\ge', desc: 'Pertidaksamaan' },
    { name: 'Tidak Sama Dengan', label: '≠', latex: '\\ne', desc: 'Pertidaksamaan' },
    { name: 'Mendekati', label: '≈', latex: '\\approx', desc: 'Aproksimasi' },
    { name: 'Tak Hingga', label: '∞', latex: '\\infty', desc: 'Infinity' },
    { name: 'Elemen Dari', label: '∈', latex: 'x \\in \\mathbb{R}', desc: 'Anggota himpunan bilangan real' },
    { name: 'Bukan Elemen', label: '∉', latex: 'x \\notin A', desc: 'Bukan anggota himpunan' },
    { name: 'Himpunan Bagian', label: '⊂', latex: 'A \\subset B', desc: 'Subset' },
    { name: 'Irisan', label: '∩', latex: 'A \\cap B', desc: 'Irisan himpunan' },
    { name: 'Gabungan', label: '∪', latex: 'A \\cup B', desc: 'Gabungan himpunan' }
  ],
  greek: [
    { name: 'Alpha', label: 'α', latex: '\\alpha', desc: 'Alpha' },
    { name: 'Beta', label: 'β', latex: '\\beta', desc: 'Beta' },
    { name: 'Gamma', label: 'γ', latex: '\\gamma', desc: 'Gamma' },
    { name: 'Theta', label: 'θ', latex: '\\theta', desc: 'Theta' },
    { name: 'Lambda', label: 'λ', latex: '\\lambda', desc: 'Lambda' },
    { name: 'Mu', label: 'μ', latex: '\\mu', desc: 'Mu' },
    { name: 'Pi', label: 'π', latex: '\\pi', desc: 'Pi' },
    { name: 'Sigma Kecil', label: 'σ', latex: '\\sigma', desc: 'Standar deviasi' },
    { name: 'Delta Besar', label: 'Δ', latex: '\\Delta', desc: 'Perubahan / diskriminan' },
    { name: 'Omega Besar', label: 'Ω', latex: '\\Omega', desc: 'Omega' }
  ]
};

const currentCategoryTemplates = computed(() => {
  return mathTemplates[mathActiveCategory.value] || mathTemplates.powers_roots;
});

const applyMathTemplate = (tpl) => {
  if (!mathFormulaInput.value.trim()) {
    mathFormulaInput.value = tpl;
  } else {
    mathFormulaInput.value += ' ' + tpl;
  }
  renderMathPreview();
};

watch(mathFormulaInput, () => {
  renderMathPreview();
});

const renderMathPreview = () => {
  if (!mathPreviewEl.value) return;
  const raw = mathFormulaInput.value.trim();
  if (!raw) {
    mathPreviewEl.value.innerHTML = '<span class="text-white/30 text-xs italic">Pratinjau rumus KaTeX akan muncul di sini...</span>';
    return;
  }
  try {
    katex.render(raw, mathPreviewEl.value, {
      throwOnError: false,
      displayMode: mathDisplayMode.value === 'block'
    });
  } catch (e) {
    mathPreviewEl.value.innerHTML = `<span class="text-rose-400 text-xs">Rumus tidak valid: ${e.message}</span>`;
  }
};

const insertMathFormula = () => {
  try {
    const rawLatex = mathFormulaInput.value.trim();
    if (!rawLatex) return;

    const isBlock = mathDisplayMode.value === 'block';
    const renderedHtml = katex.renderToString(rawLatex, {
      throwOnError: false,
      displayMode: isBlock
    });

    let mathElementHtml = '';
    if (isBlock) {
      mathElementHtml = `
        <div class="edupath-math-block my-3 p-3 bg-black/40 border border-amber-500/30 rounded-xl text-center select-none cursor-pointer hover:border-[#c0ff00]/60 transition-colors" data-latex="${encodeURIComponent(rawLatex)}" data-display-mode="block" title="Klik untuk edit rumus KaTeX: ${rawLatex}">
          ${renderedHtml}
        </div>
        <p><br></p>
      `;
    } else {
      mathElementHtml = `
        <span class="edupath-math-inline mx-1 px-1.5 py-0.5 rounded bg-amber-400/10 border border-amber-400/25 text-amber-200 select-none cursor-pointer hover:border-[#c0ff00] transition-colors inline-block" data-latex="${encodeURIComponent(rawLatex)}" data-display-mode="inline" title="Klik untuk edit rumus KaTeX: ${rawLatex}">
          ${renderedHtml}
        </span>
      `;
    }

    if (editorEl.value) editorEl.value.focus();
    document.execCommand('insertHTML', false, mathElementHtml);
    showMathModal.value = false;
    onContentInput();
  } catch (e) {
    alert('Gagal membuat rumus matematika: ' + e.message);
  }
};

// Handle clicking on existing math formulas to re-edit
const handleEditorClick = (e) => {
  const target = e.target;
  const mathEl = target.closest('.edupath-math-inline, .edupath-math-block');
  if (mathEl) {
    const rawLatex = mathEl.getAttribute('data-latex');
    if (rawLatex) {
      mathFormulaInput.value = decodeURIComponent(rawLatex);
      mathDisplayMode.value = mathEl.getAttribute('data-display-mode') || (mathEl.classList.contains('edupath-math-block') ? 'block' : 'inline');
      showMathModal.value = true;
      nextTick(() => renderMathPreview());
    }
  }
};

// Special Callouts
const insertCallout = (type) => {
  showCalloutDropdown.value = false;

  const configs = {
    definition: {
      title: 'Definisi Konsep',
      icon: 'ph-book-open',
      color: 'border-cyan-500/40 bg-cyan-500/10 text-cyan-200'
    },
    note: {
      title: 'Catatan Penting',
      icon: 'ph-info',
      color: 'border-indigo-500/40 bg-indigo-500/10 text-indigo-200'
    },
    tip: {
      title: 'Tips & Trik EduPath',
      icon: 'ph-lightbulb',
      color: 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
    },
    example: {
      title: 'Contoh Soal & Analisis',
      icon: 'ph-lightning',
      color: 'border-amber-500/40 bg-amber-500/10 text-amber-200'
    },
    warning: {
      title: 'Peringatan & Jebakan Umum',
      icon: 'ph-warning',
      color: 'border-rose-500/40 bg-rose-500/10 text-rose-200'
    }
  };

  const cfg = configs[type] || configs.note;

  const calloutHtml = `
    <div class="edupath-callout edupath-callout-${type} my-4 p-4 rounded-xl border ${cfg.color} space-y-1">
      <div class="font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 opacity-90">
        <i class="ph-bold ${cfg.icon}"></i> ${cfg.title}
      </div>
      <div class="text-sm font-normal leading-relaxed text-white/90">
        Tuliskan uraian ${cfg.title.toLowerCase()} di sini...
      </div>
    </div>
    <p><br></p>
  `;

  if (editorEl.value) editorEl.value.focus();
  document.execCommand('insertHTML', false, calloutHtml);
  onContentInput();
};

// Section Break / Bab
const insertSectionBreak = () => {
  const sectionHtml = `
    <div class="edupath-section-header my-6 pt-4 border-t-2 border-white/15">
      <h2 class="text-base font-black text-[#c0ff00] uppercase tracking-wide">Bagian Baru: [Tuliskan Judul Bagian]</h2>
      <p class="text-xs text-white/60">[Tuliskan penjelasan pengantar sub-topik]</p>
    </div>
    <p><br></p>
  `;
  if (editorEl.value) editorEl.value.focus();
  document.execCommand('insertHTML', false, sectionHtml);
  onContentInput();
};

// Link Modal

const openLinkModal = () => {
  saveCurrentSelection();
  linkText.value = window.getSelection().toString() || '';
  linkUrl.value = '';
  showLinkModal.value = true;
};

const insertLink = () => {
  if (!linkUrl.value) return;
  if (editorEl.value) editorEl.value.focus();

  if (savedSelection.value) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedSelection.value);
  }

  const text = linkText.value || linkUrl.value;
  const linkHtml = `<a href="${linkUrl.value}" target="_blank" rel="noopener noreferrer" class="text-sky-400 underline font-bold hover:text-sky-300">${text}</a>`;
  document.execCommand('insertHTML', false, linkHtml);
  showLinkModal.value = false;
  onContentInput();
};

// Word and Char Counter
const wordCount = ref(0);
const charCount = ref(0);

const updateCounts = () => {
  if (!editorEl.value) return;
  const text = editorEl.value.innerText || '';
  charCount.value = text.length;
  wordCount.value = text.trim() ? text.trim().split(/\s+/).length : 0;
};

// Content Input & Autosave Debounce
let autosaveTimer = null;

const onContentInput = () => {
  if (!editorEl.value) return;
  const content = editorEl.value.innerHTML;
  emit('update:modelValue', content);
  emit('change', content);
  updateCounts();

  saveStatus.value = 'saving';
  clearTimeout(autosaveTimer);
  autosaveTimer = setTimeout(() => {
    saveDraft(content);
  }, 1200);
};

const saveDraft = (content) => {
  try {
    const payload = {
      content,
      timestamp: new Date().toISOString(),
      materialId: props.materialId || 'new'
    };
    localStorage.setItem(draftStorageKey.value, JSON.stringify(payload));
    saveStatus.value = 'saved';
    const now = new Date();
    lastSavedTime.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    emit('autosaved', payload);
  } catch (err) {
    console.error('Autosave failed:', err);
    saveStatus.value = 'error';
  }
};

const checkRecoverableDraft = () => {
  try {
    const raw = localStorage.getItem(draftStorageKey.value);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed.content && parsed.content !== props.modelValue) {
        hasRecoverableDraft.value = true;
      }
    }
  } catch (e) {}
};

const restoreDraft = () => {
  try {
    const raw = localStorage.getItem(draftStorageKey.value);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed.content && editorEl.value) {
        editorEl.value.innerHTML = parsed.content;
        emit('update:modelValue', parsed.content);
        emit('change', parsed.content);
        hasRecoverableDraft.value = false;
        alert('Draft materi berhasil dipulihkan.');
      }
    }
  } catch (e) {}
};

// Sanitized HTML for Preview Mode
const sanitizedHtml = computed(() => {
  return props.modelValue || '<p class="text-white/40 italic">Belum ada konten materi yang ditulis.</p>';
});

// Render KaTeX in Preview when switching views
watch(activeView, async (newVal) => {
  if (newVal === 'preview') {
    await nextTick();
    if (previewContentEl.value) {
      const mathBlocks = previewContentEl.value.querySelectorAll('.edupath-math-block');
      mathBlocks.forEach(block => {
        const raw = block.getAttribute('data-latex');
        if (raw) {
          try {
            katex.render(decodeURIComponent(raw), block, {
              throwOnError: false,
              displayMode: true
            });
          } catch (e) {}
        }
      });
    }
  }
});

// Initialize Editor with modelValue
watch(() => props.modelValue, (newVal) => {
  if (editorEl.value && editorEl.value.innerHTML !== newVal) {
    editorEl.value.innerHTML = newVal || '';
    updateCounts();
  }
});

onMounted(() => {
  if (editorEl.value) {
    editorEl.value.innerHTML = props.modelValue || '';
    updateCounts();
  }
  checkRecoverableDraft();
});
</script>

<style scoped>
/* Tool button styling */
.tool-btn {
  padding: 0.35rem 0.5rem;
  border-radius: 0.5rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.7);
  background: transparent;
  transition: all 0.15s ease;
  cursor: pointer;
}
.tool-btn:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
}
.tool-select {
  padding: 0.35rem 0.5rem;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  outline: none;
}
.tool-select option {
  background: #0e1726;
  color: #ffffff;
}
.divider {
  width: 1px;
  height: 1.25rem;
  background: rgba(255, 255, 255, 0.1);
  margin: 0 0.25rem;
}

/* Callout dropdown options */
.callout-opt {
  width: 100%;
  text-align: left;
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.15s ease;
  cursor: pointer;
}

/* Modal Form Styles */
.input-field {
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  color: #ffffff;
  font-size: 0.75rem;
  outline: none;
  transition: border-color 0.15s;
}
.input-field:focus {
  border-color: rgba(192, 255, 0, 0.5);
}
.btn-primary {
  padding: 0.4rem 1rem;
  background: #c0ff00;
  color: #000000;
  font-size: 0.75rem;
  font-weight: 900;
  border-radius: 0.6rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-primary:hover:not(:disabled) {
  background: #b0ef00;
}
.btn-primary:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.btn-cancel {
  padding: 0.4rem 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 0.6rem;
  cursor: pointer;
}
.btn-cancel:hover {
  color: #ffffff;
}
.math-btn {
  padding: 0.25rem 0.5rem;
  border-radius: 0.4rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #c0ff00;
  font-family: serif;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.15s;
}
.math-btn:hover {
  background: rgba(192, 255, 0, 0.15);
  border-color: rgba(192, 255, 0, 0.4);
}

/* Prose formatting inside editor and preview */
.edupath-prose :deep(h1) {
  font-size: 1.5rem;
  font-weight: 900;
  margin-top: 1.25rem;
  margin-bottom: 0.5rem;
  color: #ffffff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  padding-bottom: 0.25rem;
}
.edupath-prose :deep(h2) {
  font-size: 1.25rem;
  font-weight: 800;
  margin-top: 1rem;
  margin-bottom: 0.4rem;
  color: #c0ff00;
}
.edupath-prose :deep(h3) {
  font-size: 1.1rem;
  font-weight: 800;
  margin-top: 0.85rem;
  margin-bottom: 0.35rem;
  color: #e2e8f0;
}
.edupath-prose :deep(h4) {
  font-size: 0.95rem;
  font-weight: 700;
  margin-top: 0.75rem;
  margin-bottom: 0.25rem;
  color: #94a3b8;
}
.edupath-prose :deep(p) {
  margin-bottom: 0.6rem;
  line-height: 1.65;
}
.edupath-prose :deep(ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}
.edupath-prose :deep(ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}
.edupath-prose :deep(li) {
  margin-bottom: 0.25rem;
}
.edupath-prose :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 0.75rem 0;
}
.edupath-prose :deep(th),
.edupath-prose :deep(td) {
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 0.5rem 0.75rem;
}
.edupath-prose :deep(blockquote) {
  border-left: 4px solid #c0ff00;
  padding-left: 1rem;
  margin: 0.75rem 0;
  color: rgba(255, 255, 255, 0.8);
  font-style: italic;
}
.edupath-prose :deep(img) {
  max-width: 100%;
  height: auto;
}
.edupath-prose :deep(hr) {
  border: none;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
  margin: 1.5rem 0;
}
.edupath-prose :deep(sup) {
  font-size: 0.75em;
  vertical-align: super;
  line-height: 0;
}
.edupath-prose :deep(sub) {
  font-size: 0.75em;
  vertical-align: sub;
  line-height: 0;
}
.edupath-prose :deep(.edupath-math-inline) {
  display: inline-flex;
  align-items: center;
  padding: 0 0.25rem;
  margin: 0 0.15rem;
  border-radius: 0.25rem;
  background: rgba(251, 191, 36, 0.1);
  border: 1px solid rgba(251, 191, 36, 0.25);
  vertical-align: middle;
}
.edupath-prose :deep(.edupath-math-block) {
  display: block;
  margin: 0.75rem 0;
  padding: 0.75rem;
  border-radius: 0.75rem;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(251, 191, 36, 0.25);
  text-align: center;
  overflow-x: auto;
}

/* Empty placeholder */
[contenteditable="true"]:empty:before {
  content: attr(data-placeholder);
  color: rgba(255, 255, 255, 0.3);
  pointer-events: none;
  display: block;
}
</style>
