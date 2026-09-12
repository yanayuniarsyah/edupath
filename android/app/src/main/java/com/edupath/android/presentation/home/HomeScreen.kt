package com.edupath.android.presentation.home

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewmodel.compose.viewModel
import com.edupath.android.core.datastore.SessionStoreImpl
import com.edupath.android.core.network.RetrofitClient
import com.edupath.android.data.remote.AuthApi

class HomeViewModelFactory(
    private val authApi: AuthApi,
    private val sessionStore: SessionStoreImpl
) : ViewModelProvider.Factory {
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        return HomeViewModel(authApi, sessionStore) as T
    }
}

@Composable
fun HomeScreen(
    onNavigateLogout: () -> Unit
) {
    val context = LocalContext.current
    val store = remember { SessionStoreImpl(context) }
    val api = remember { RetrofitClient.create(store).create(AuthApi::class.java) }
    val factory = remember { HomeViewModelFactory(api, store) }
    val viewModel: HomeViewModel = viewModel(factory = factory)
    
    val state by viewModel.state.collectAsState()

    LaunchedEffect(state) {
        if (state is HomeState.LoggedOut) {
            onNavigateLogout()
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("EduPath Dashboard", fontSize = 28.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
        
        Spacer(modifier = Modifier.height(32.dp))
        
        when (state) {
            is HomeState.Loading -> {
                CircularProgressIndicator()
            }
            is HomeState.Success -> {
                val user = state as HomeState.Success
                Text("Selamat datang,", fontSize = 18.sp)
                Text(user.name, fontSize = 24.sp, fontWeight = FontWeight.Medium)
                Text(user.email, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                
                Spacer(modifier = Modifier.height(48.dp))
                
                Button(
                    onClick = { viewModel.logout() },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Logout")
                }
            }
            else -> {}
        }
    }
}
